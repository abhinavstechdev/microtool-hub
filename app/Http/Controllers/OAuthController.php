<?php

namespace App\Http\Controllers;

use App\Models\OAuthAccessToken;
use App\Models\OAuthAuthCode;
use App\Models\OAuthClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class OAuthController extends Controller
{
    /**
     * RFC 9728 Protected Resource Discovery
     */
    public function protectedResource(): JsonResponse
    {
        return response()->json([
            'resource' => url('/mcp'),
            'authorization_servers' => [
                url('/')
            ],
            'scopes_supported' => [
                'tools:read',
                'projects:create',
                'projects:read',
                'files:write',
                'files:delete',
                'projects:publish'
            ],
        ], 200, [
            'Content-Type' => 'application/json',
            'Access-Control-Allow-Origin' => '*',
        ]);
    }

    /**
     * RFC 8414 Authorization Server Metadata
     */
    public function authorizationServer(): JsonResponse
    {
        return response()->json([
            'issuer' => url('/'),
            'authorization_endpoint' => url('/oauth/authorize'),
            'token_endpoint' => url('/oauth/token'),
            'registration_endpoint' => url('/oauth/register'),
            'revocation_endpoint' => url('/oauth/revoke'),
            'client_id_metadata_document_supported' => true,
            'authorization_response_iss_parameter_supported' => true,
            'response_types_supported' => ['code'],
            'grant_types_supported' => ['authorization_code'],
            'code_challenge_methods_supported' => ['S256'],
            'scopes_supported' => [
                'tools:read',
                'projects:create',
                'projects:read',
                'files:write',
                'files:delete',
                'projects:publish'
            ],
            'token_endpoint_auth_methods_supported' => ['none', 'client_secret_post', 'client_secret_basic'],
        ], 200, [
            'Content-Type' => 'application/json',
            'Access-Control-Allow-Origin' => '*',
        ]);
    }

    /**
     * RFC 7591 Dynamic Client Registration (DCR)
     */
    public function registerClient(Request $request): JsonResponse
    {
        $redirectUris = $request->input('redirect_uris', []);
        if (is_array($redirectUris)) {
            $redirectUrisStr = implode("\n", $redirectUris);
        } else {
            $redirectUrisStr = (string) $redirectUris;
        }

        $clientId = 'dcr_' . Str::random(32);
        $clientSecret = 'sec_' . Str::random(32);
        $clientName = $request->input('client_name', 'ChatGPT MCP Client');
        $authMethod = $request->input('token_endpoint_auth_method', 'none');

        OAuthClient::create([
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'name' => $clientName,
            'redirect_uris' => $redirectUrisStr,
            'is_confidential' => $authMethod !== 'none',
            'revoked' => false,
        ]);

        return response()->json([
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'client_id_issued_at' => time(),
            'client_name' => $clientName,
            'redirect_uris' => is_array($redirectUris) ? $redirectUris : [$redirectUrisStr],
            'grant_types' => ['authorization_code'],
            'response_types' => ['code'],
            'token_endpoint_auth_method' => $authMethod,
        ], 201, [
            'Content-Type' => 'application/json',
            'Cache-Control' => 'no-store',
            'Pragma' => 'no-cache',
            'Access-Control-Allow-Origin' => '*',
        ]);
    }

    /**
     * Domain verification challenge endpoint for OpenAI review
     */
    public function openaiChallenge(): Response
    {
        $token = env('OPENAI_APPS_CHALLENGE_TOKEN', 'microtool_hub_challenge_verify_token_2026');
        return response($token, 200, [
            'Content-Type' => 'text/plain',
        ]);
    }

    /**
     * OAuth Authorize Consent Screen
     */
    public function authorizePrompt(Request $request)
    {
        $clientId = $request->query('client_id');
        $redirectUri = $request->query('redirect_uri');
        $state = $request->query('state');
        $scope = $request->query('scope', 'tools:read projects:read projects:create files:write');
        $codeChallenge = $request->query('code_challenge');
        $codeChallengeMethod = $request->query('code_challenge_method', 'S256');

        $client = OAuthClient::where('client_id', $clientId)->where('revoked', false)->first();
        if (!$client && (str_starts_with((string) $clientId, 'https://') || str_starts_with((string) $clientId, 'chatgpt_'))) {
            $client = OAuthClient::firstOrCreate(
                ['client_id' => $clientId],
                [
                    'client_secret' => null,
                    'name' => str_starts_with((string) $clientId, 'https://') ? 'ChatGPT (CIMD)' : 'ChatGPT Official Plugin Connector',
                    'redirect_uris' => $redirectUri ?: "https://chatgpt.com/connector_platform_oauth_redirect\nhttps://chatgpt.com/connector/oauth/*\nhttps://chatgpt.com/aip/plugin-oauth/callback",
                    'is_confidential' => false,
                    'revoked' => false,
                ]
            );
        }

        if (!$client) {
            return response()->view('errors.oauth', ['error' => 'Invalid or unrecognized client_id.'], 400);
        }

        if (!Auth::check()) {
            // Save request query into session and redirect to login
            session(['oauth_pending_request' => $request->all()]);
            return redirect()->route('login');
        }

        return view('oauth.authorize', [
            'client' => $client,
            'user' => Auth::user(),
            'redirectUri' => $redirectUri,
            'state' => $state,
            'scope' => $scope,
            'codeChallenge' => $codeChallenge,
            'codeChallengeMethod' => $codeChallengeMethod,
        ]);
    }

    /**
     * Approve OAuth authorization request
     */
    public function approve(Request $request)
    {
        $request->validate([
            'client_id' => 'required|string',
            'redirect_uri' => 'required|string',
            'code_challenge' => 'nullable|string',
            'code_challenge_method' => 'nullable|string',
            'state' => 'nullable|string',
            'scope' => 'nullable|string',
        ]);

        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $clientId = $request->input('client_id');
        if ((str_starts_with($clientId, 'https://') || str_starts_with($clientId, 'chatgpt_')) && !OAuthClient::where('client_id', $clientId)->exists()) {
            OAuthClient::firstOrCreate(
                ['client_id' => $clientId],
                [
                    'client_secret' => null,
                    'name' => str_starts_with($clientId, 'https://') ? 'ChatGPT (CIMD)' : 'ChatGPT Official Plugin Connector',
                    'redirect_uris' => $request->input('redirect_uri'),
                    'is_confidential' => false,
                    'revoked' => false,
                ]
            );
        }

        $code = Str::random(40);
        OAuthAuthCode::create([
            'user_id' => Auth::id(),
            'client_id' => $clientId,
            'code' => $code,
            'scopes' => $request->input('scope', 'tools:read projects:read projects:create files:write'),
            'code_challenge' => $request->input('code_challenge'),
            'code_challenge_method' => $request->input('code_challenge_method', 'S256'),
            'redirect_uri' => $request->input('redirect_uri'),
            'expires_at' => now()->addMinutes(10),
            'revoked' => false,
            'created_at' => now(),
        ]);

        $redirectUri = $request->input('redirect_uri');
        $delimiter = str_contains($redirectUri, '?') ? '&' : '?';
        $params = http_build_query([
            'code' => $code,
            'state' => $request->input('state'),
            'iss' => url('/'),
        ]);

        return redirect()->away($redirectUri . $delimiter . $params);
    }

    /**
     * OAuth 2.1 Token Exchange (PKCE S256)
     */
    public function token(Request $request): JsonResponse
    {
        $grantType = $request->input('grant_type');
        if ($grantType !== 'authorization_code') {
            return response()->json([
                'error' => 'unsupported_grant_type',
                'error_description' => 'Grant type must be authorization_code.'
            ], 400);
        }

        $code = $request->input('code');
        $clientId = $request->input('client_id');
        $codeVerifier = $request->input('code_verifier');
        $redirectUri = $request->input('redirect_uri');

        // Allow Basic auth header for clientId / clientSecret
        if (!$clientId && $request->header('PHP_AUTH_USER')) {
            $clientId = $request->header('PHP_AUTH_USER');
        }

        $authCode = OAuthAuthCode::where('code', $code)
            ->where('revoked', false)
            ->first();

        if (!$authCode || $authCode->expires_at->isPast()) {
            return response()->json([
                'error' => 'invalid_grant',
                'error_description' => 'Authorization code is invalid or has expired.'
            ], 400);
        }

        if ($authCode->client_id !== $clientId) {
            return response()->json([
                'error' => 'invalid_client',
                'error_description' => 'Client ID does not match authorization code.'
            ], 400);
        }

        // Verify PKCE if challenge was provided
        if ($authCode->code_challenge) {
            if (!$codeVerifier) {
                return response()->json([
                    'error' => 'invalid_request',
                    'error_description' => 'code_verifier is required for PKCE verification.'
                ], 400);
            }

            if ($authCode->code_challenge_method === 'S256') {
                $computedChallenge = rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');
                if (!hash_equals($authCode->code_challenge, $computedChallenge)) {
                    return response()->json([
                        'error' => 'invalid_grant',
                        'error_description' => 'PKCE verification failed.'
                    ], 400);
                }
            } elseif ($authCode->code_challenge !== $codeVerifier) {
                return response()->json([
                    'error' => 'invalid_grant',
                    'error_description' => 'Plain PKCE verification failed.'
                ], 400);
            }
        }

        // Revoke code so it can only be used once
        $authCode->update(['revoked' => true]);

        // Generate Access Token (30 days validity)
        $rawToken = 'mcp_' . Str::random(60);
        $tokenHash = hash('sha256', $rawToken);

        OAuthAccessToken::create([
            'user_id' => $authCode->user_id,
            'client_id' => $clientId,
            'token_hash' => $tokenHash,
            'scopes' => $authCode->scopes,
            'expires_at' => now()->addDays(30),
            'revoked' => false,
        ]);

        return response()->json([
            'access_token' => $rawToken,
            'token_type' => 'Bearer',
            'expires_in' => 2592000,
            'scope' => $authCode->scopes,
        ], 200, [
            'Cache-Control' => 'no-store',
            'Pragma' => 'no-cache',
        ]);
    }

    /**
     * Revoke access token
     */
    public function revoke(Request $request): JsonResponse
    {
        $token = $request->input('token');
        if ($token) {
            $tokenHash = hash('sha256', $token);
            OAuthAccessToken::where('token_hash', $tokenHash)->update(['revoked' => true]);
        }
        return response()->json(['status' => 'revoked']);
    }
}
