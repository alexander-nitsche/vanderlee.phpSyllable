<?php

namespace Vanderlee\SyllableBuildTest;

use Vanderlee\SyllableBuild\Git;
use Vanderlee\SyllableBuild\ReleaseManager;
use Vanderlee\SyllableBuild\SemanticVersioning;

class ReleaseManagerTest extends AbstractTestCase
{
    /**
     * @var ReleaseManager
     */
    protected $releaseManager;

    /**
     * Note: Use the @before annotation instead of the reserved setUp()
     * to be compatible with a wide range of PHPUnit versions.
     *
     * @before
     */
    protected function setUpFixture()
    {
        $this->releaseManager = new ReleaseManager();

        $this->createTestDirectory();
    }

    /**
     * Note: Use the @after annotation instead of the reserved tearDown()
     * to be compatible with a wide range of PHPUnit versions.
     *
     * @after
     */
    protected function tearDownFixture()
    {
        $this->removeTestDirectory();
    }

    /**
     * @test
     */
    public function delegateSucceeds()
    {
        $originalReadme = trim('
Syllable
========
Version 1.5.3

..

Changes
-------
1.5.3
-   Fixed PHP 7.4 compatibility (#37) by @Dargmuesli.
        ');

        // Stub only the minimum required, we rely here in part on an
        // existing Git API that is available in most test environments.
        $gitStub = $this->getMockBuilder(Git::class)->getMock();
        $gitStub->expects($this->any())->method('getTag')->willReturn('1.5.3');
        $gitStub->expects($this->any())->method('getTagLong')->willReturn('1.5.3-9-g69c4c1b');
        $gitStub->expects($this->any())->method('getSubjectsSinceLastRelease')->willReturn([
            'Fix small typo in README and add \'use\' in example.',
            'Use same code format as in src/Source/File.php',
            'Fix opening brace',
            'Remove whitespace',
            'Fix closing brace',
            'Use PHP syntax highlighting',
        ]);

        $expectedOutputRegex = '#Create release 1.5.4.#';
        $expectedReadme = trim('
Syllable
========
Version 1.5.4

..

Changes
-------
1.5.4
-   Fix small typo in README and add \'use\' in example.
-   Use same code format as in src/Source/File.php
-   Fix opening brace
-   Remove whitespace
-   Fix closing brace
-   Use PHP syntax highlighting

1.5.3
-   Fixed PHP 7.4 compatibility (#37) by @Dargmuesli.
        ');

        $this->addFileToTestDirectory('ORIGINAL-README.md', $originalReadme);
        $this->addFileToTestDirectory('EXPECTED-README.md', $expectedReadme);

        $releaseType = SemanticVersioning::PATCH_RELEASE;
        $originalReadmeFile = $this->getPathOfTestDirectoryFile('ORIGINAL-README.md');
        $expectedReadmeFile = $this->getPathOfTestDirectoryFile('EXPECTED-README.md');

        $releaseManager = new ReleaseManager();
        $releaseManager->setReleaseType($releaseType);
        $releaseManager->setReadmeFile($originalReadmeFile);
        $releaseManager->setGit($gitStub);
        $result = $releaseManager->delegate();

        $this->assertTrue($result);
        $this->expectOutputRegex($expectedOutputRegex);
        $this->assertFileEquals($expectedReadmeFile, $originalReadmeFile);
    }
}
