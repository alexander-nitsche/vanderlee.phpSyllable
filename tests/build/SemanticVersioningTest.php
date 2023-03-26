<?php

namespace Vanderlee\SyllableBuildTest;

use PHPUnit\Framework\TestCase;
use Vanderlee\SyllableBuild\SemanticVersioning;

class SemanticVersioningTest extends TestCase
{
    /**
     * @var SemanticVersioning
     */
    protected $object;

    /**
     * Note: Use the @before annotation instead of the reserved setUp()
     * to be compatible with a wide range of PHPUnit versions.
     *
     * @before
     */
    protected function setUpFixture()
    {
        $this->object = new SemanticVersioning();
    }

    public function getNextReleaseTagDataProvider()
    {
        return [
            ['1.0.1', SemanticVersioning::PATCH_RELEASE, '1.0.2'],
            ['1.0.9', SemanticVersioning::PATCH_RELEASE, '1.0.10'],
            ['1.0.1', SemanticVersioning::MINOR_RELEASE, '1.1'],
            ['1.0.1', SemanticVersioning::MAJOR_RELEASE, '2'],
            ['1.1', SemanticVersioning::PATCH_RELEASE, '1.1.1'],
            ['1.1', SemanticVersioning::MINOR_RELEASE, '1.2'],
            ['1.1', SemanticVersioning::MAJOR_RELEASE, '2'],
            ['1', SemanticVersioning::PATCH_RELEASE, '1.0.1'],
            ['1', SemanticVersioning::MINOR_RELEASE, '1.1'],
            ['1', SemanticVersioning::MAJOR_RELEASE, '2'],
            ['v1.0.1', SemanticVersioning::PATCH_RELEASE, 'v1.0.2'],
        ];
    }

    /**
     * @test
     *
     * @dataProvider getNextReleaseTagDataProvider
     */
    public function getNextReleaseTag($tag, $releaseType, $expected)
    {
        $subject = new SemanticVersioning();

        $actual = $subject->getNextReleaseTag($tag, $releaseType);

        $this->assertEquals($expected, $actual);
    }
}