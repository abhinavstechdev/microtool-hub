<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Services\ProjectStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class ProjectStorageSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected ProjectStorageService $storage;
    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->storage = new ProjectStorageService();
        $this->project = Project::first();
    }

    public function test_rejects_path_traversal_attempts(): void
    {
        $traversalPaths = [
            '../etc/passwd',
            '..\\windows\\system32',
            'subfolder/../../secret.env',
            "index.html\0.php",
            'C:/boot.ini',
        ];

        foreach ($traversalPaths as $badPath) {
            $threw = false;
            try {
                $this->storage->writeFile($this->project, $badPath, 'alert(1)');
            } catch (RuntimeException $e) {
                $threw = true;
            }
            $this->assertTrue($threw, "Path '{$badPath}' was not rejected as traversal!");
        }
    }

    public function test_rejects_prohibited_extensions(): void
    {
        $prohibitedFiles = [
            'shell.php',
            'exploit.phtml',
            'script.sh',
            'payload.exe',
            '.htaccess',
            '.env',
        ];

        foreach ($prohibitedFiles as $badFile) {
            $threw = false;
            try {
                $this->storage->writeFile($this->project, $badFile, 'content');
            } catch (RuntimeException $e) {
                $threw = true;
            }
            $this->assertTrue($threw, "Extension for '{$badFile}' was not rejected!");
        }
    }

    public function test_allows_valid_web_files_and_atomic_writes(): void
    {
        $file = $this->storage->writeFile($this->project, 'component.js', 'console.log("ok");');
        $this->assertEquals('component.js', $file->path);
        $this->assertEquals(strlen('console.log("ok");'), $file->size_bytes);

        $readBack = $this->storage->readFile($this->project, 'component.js');
        $this->assertEquals('console.log("ok");', $readBack);

        $this->storage->deleteFile($this->project, 'component.js');
    }
}
