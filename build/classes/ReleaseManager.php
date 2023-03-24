<?php

namespace Vanderlee\SyllableBuild;

class ReleaseManager extends Manager
{
    const MAJOR_RELEASE = 0;
    const MINOR_RELEASE = 1;
    const PATCH_RELEASE = 2;

    protected $releaseType;

    public function __construct()
    {
        parent::__construct();

        $this->releaseType = self::PATCH_RELEASE;
    }

    /**
     * @param int $releaseType
     */
    public function setReleaseType($releaseType)
    {
        $this->releaseType = $releaseType;
    }

    /**
     * @return bool
     */
    public function release()
    {
        try {
            $gitDiff = $this->exec('git diff --name-only');

            if (!empty($gitDiff)) {
                $this->error('The project has uncommitted changes.');
                $this->error('Aborting.');

                return false;
            }

            $branch = $this->exec('git rev-parse --abbrev-ref HEAD');
            $tag = $this->exec('git describe --tags --abbrev=0');
            $tagLong = $this->exec('git describe --tags');

            if ($tag === $tagLong) {
                $this->error(sprintf('Current %s branch is already tagged (%s).', $branch, $tag));
                $this->error('Aborting.');

                return false;
            }

            $releaseTag = $this->getReleaseTag($tag);

            $this->info(sprintf('Create release %s.', $releaseTag));
            $this->info('Update README.md.');

            $subjects = $this->exec(sprintf('git log --no-merges --pretty="format:%%s" %s..HEAD', $tag), true);

            $changelog = "$releaseTag\n";
            foreach ($subjects as $subject) {
                $changelog .= "-   $subject\n";
            }

            $readmePath = __DIR__.'/../../README.md';
            $readme = file($readmePath);
            $readmeContent = '';
            foreach ($readme as $line) {
                if (strpos($line, "Version $tag") === 0) {
                    $readmeContent .= str_replace($tag, $releaseTag, $line);
                } elseif (strpos($line, $tag) === 0) {
                    $readmeContent .= str_replace($tag, "$changelog\n$tag", $line);
                } else {
                    $readmeContent .= $line;
                }
            }
            file_put_contents($readmePath, $readmeContent);

            $gitDiff = $this->exec('git diff --name-only');

            if (empty($gitDiff)) {
                $this->error('Could not update README.md.');
                $this->error('The format has probably changed.');
                $this->error('Aborting.');

                return false;
            }

            $this->info('Create release commit.');

            $this->exec('git add -u');
            $this->exec(sprintf('git commit -m "Release %s"', $releaseTag));
            $this->exec(sprintf('git tag %s', $releaseTag));
        } catch (ManagerException $exception) {
            $this->error($exception->getMessage());
            $this->error('Aborting.');

            return false;
        }

        return true;
    }

    /**
     * @param $tag
     * @return string
     */
    protected function getReleaseTag($tag)
    {
        $tagPrefix = substr($tag, 0, strcspn($tag, '0123456789'));
        $tagVersion = substr($tag, strlen($tagPrefix));
        $tagVersionParts = explode('.', $tagVersion);
        $releaseVersionParts = array_slice($tagVersionParts, 0, $this->releaseType + 1);
        $releaseVersionParts[$this->releaseType]++;
        $releaseVersion = implode('.', $releaseVersionParts);

        return $tagPrefix.$releaseVersion;
    }
}