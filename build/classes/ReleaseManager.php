<?php

namespace Vanderlee\SyllableBuild;

class ReleaseManager extends Manager
{
    const MAJOR_RELEASE = 0;
    const MINOR_RELEASE = 1;
    const PATCH_RELEASE = 2;

    protected $releaseType;

    protected $branch;

    protected $tag;

    protected $tagLong;

    protected $releaseTag;

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
    public function delegate()
    {
        try {
            $this->getContext();
            $this->checkPrerequisites();
            $this->info(sprintf('Create release %s.', $this->releaseTag));
            $this->info('Update README.md.');
            $this->updateReadme();
            $this->checkPostConditions();
            $this->info('Create release commit.');
            $this->createCommit();
        } catch (ManagerException $exception) {
            $this->error($exception->getMessage());
            $this->error('Aborting.');

            return false;
        }

        return true;
    }

    /**
     * @throws ManagerException
     *
     * @return void
     */
    protected function getContext()
    {
        $this->branch = $this->getBranch();
        $this->tag = $this->getTag();
        $this->tagLong = $this->getTagLong();
        $this->releaseTag = $this->createReleaseTag();
    }

    /**
     * @throws ManagerException
     *
     * @return string
     */
    protected function getBranch()
    {
        return $this->exec('git rev-parse --abbrev-ref HEAD');
    }

    /**
     * @throws ManagerException
     *
     * @return string
     */
    protected function getTag()
    {
        return $this->exec('git describe --tags --abbrev=0');
    }

    /**
     * @throws ManagerException
     *
     * @return string
     */
    protected function getTagLong()
    {
        return $this->exec('git describe --tags');
    }

    /**
     * @return string
     */
    protected function createReleaseTag()
    {
        $tagPrefix = substr($this->tag, 0, strcspn($this->tag, '0123456789'));
        $tagVersion = substr($this->tag, strlen($tagPrefix));
        $tagVersionParts = explode('.', $tagVersion);
        $releaseVersionParts = array_slice($tagVersionParts, 0, $this->releaseType + 1);
        $releaseVersionParts[$this->releaseType]++;
        $releaseVersion = implode('.', $releaseVersionParts);

        return $tagPrefix.$releaseVersion;
    }

    /**
     * @throws ManagerException
     *
     * @return void
     */
    protected function checkPrerequisites()
    {
        if (!$this->hasCleanWorkingTree()) {
            throw new ManagerException(
                'The project has uncommitted changes.'
            );
        }

        if ($this->isBranchTagged()) {
            throw new ManagerException(sprintf(
                'Current %s branch is already tagged (%s).',
                $this->branch,
                $this->tag
            ));
        }
    }

    /**
     * @throws ManagerException
     *
     * @return bool
     */
    protected function hasCleanWorkingTree()
    {
        $changedFiles = $this->getChangedFiles();

        return empty($changedFiles);
    }

    /**
     * @throws ManagerException
     *
     * @return array|string
     */
    protected function getChangedFiles()
    {
        return $this->exec('git diff --name-only', true);
    }

    /**
     * @return bool
     */
    protected function isBranchTagged()
    {
        return $this->tag === $this->tagLong;
    }

    /**
     * @throws ManagerException
     *
     * @return void
     */
    protected function updateReadme()
    {
        $subjects = $this->exec(
            sprintf('git log --no-merges --pretty="format:%%s" %s..HEAD', $this->tag),
            true
        );

        $changelog = "$this->releaseTag\n";
        foreach ($subjects as $subject) {
            $changelog .= "-   $subject\n";
        }

        $readmePath = __DIR__.'/../../README.md';
        $readme = file($readmePath);
        $readmeContent = '';
        foreach ($readme as $line) {
            if (strpos($line, "Version $this->tag") === 0) {
                $readmeContent .= str_replace($this->tag, $this->releaseTag, $line);
            } elseif (strpos($line, $this->tag) === 0) {
                $readmeContent .= str_replace($this->tag, "$changelog\n$this->tag", $line);
            } else {
                $readmeContent .= $line;
            }
        }
        file_put_contents($readmePath, $readmeContent);
    }

    /**
     * @throws ManagerException
     *
     * @return void
     */
    protected function checkPostConditions()
    {
        if ($this->hasCleanWorkingTree()) {
            throw new ManagerException(
                'Could not update README.md. The format has probably changed.'
            );
        }
    }

    /**
     * @throws ManagerException
     *
     * @return void
     */
    protected function createCommit()
    {
        $this->exec('git add .');
        $this->exec(sprintf('git commit -m "Release %s"', $this->releaseTag));
        $this->exec(sprintf('git tag %s', $this->releaseTag));
    }
}