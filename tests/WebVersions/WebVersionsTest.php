<?php declare(strict_types=1);

namespace Granam\Tests\WebVersions;

use Granam\WebVersions\WebVersions;
use Granam\Git\Git;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class WebVersionsTest extends TestCase
{

    private $temporaryDir;

    protected function tearDown(): void
    {
        if ($this->temporaryDir) {
            exec(sprintf('rm -fr %s', escapeshellarg($this->temporaryDir)));
        }
    }

    /**
     * @test
     */
    public function I_can_get_last_unstable_version(): void
    {
        $webVersions = new WebVersions(new Git(), 'some repository dir');
        self::assertSame('main', $webVersions->getLastUnstableVersion(), 'Expected main as a default unstable version');
        $webVersions = new WebVersions(new Git(), 'some repository dir', 'mistress');
        self::assertSame('mistress', $webVersions->getLastUnstableVersion(), 'Expected given last unstable version to be given back');
    }

    /**
     * @test
     */
    public function I_can_get_all_minor_versions(): void
    {
        $webVersions = new WebVersions(
            $this->createGitWithAllMinorVersions('some repository dir', ['2.0', '1.1', '1.0']),
            'some repository dir',
            'mistress'
        );
        self::assertSame(['mistress', '2.0', '1.1', '1.0'], $webVersions->getAllMinorVersions());
    }

    private function createGitWithAllMinorVersions(string $expectedRepositoryDir, array $mockMinorVersions): Git
    {
        return new class($expectedRepositoryDir, $mockMinorVersions) extends Git
        {
            private $expectedRepositoryDir;
            private $mockVersions;

            public function __construct(string $expectedRepositoryDir, array $mockVersions)
            {
                parent::__construct();
                $this->expectedRepositoryDir = $expectedRepositoryDir;
                $this->mockVersions = $mockVersions;
            }

            public function getAllMinorVersions(string $repositoryDir, bool $readLocal = self::INCLUDE_LOCAL_BRANCHES, bool $readRemote = self::INCLUDE_REMOTE_BRANCHES): array
            {
                TestCase::assertTrue(method_exists(parent::class, __FUNCTION__), parent::class . ' no more has method ' . __FUNCTION__);
                TestCase::assertSame($this->expectedRepositoryDir, $repositoryDir);

                return $this->mockVersions;
            }

        };
    }

    /**
     * @test
     * @dataProvider provideLastStableVersion
     * @param string|null $lastStableMinorVersion
     * @param string $lastUnstableVersion
     * @param string|null $expectedLastStableMinorVersion
     */
    public function I_can_get_last_stable_minor_version(
        ?string $lastStableMinorVersion,
        string $lastUnstableVersion,
        ?string $expectedLastStableMinorVersion
    ): void
    {
        $dir = 'some repository dir';
        $webVersions = new WebVersions(
            $this->createGitWithLastStableMinorVersion($dir, $lastStableMinorVersion),
            $dir,
            $lastUnstableVersion
        );
        self::assertSame($expectedLastStableMinorVersion, $webVersions->getLastStableMinorVersion());
    }

    public function provideLastStableVersion(): array
    {
        return [
            'some minor version' => ['2.1', 'mistress', '2.1'],
            'only unstable version' => [null, 'mistress', null],
        ];
    }

    private function createGitWithLastStableMinorVersion(string $expectedRepositoryDir, ?string $lastStableVersion): Git
    {
        return new class($expectedRepositoryDir, $lastStableVersion) extends Git
        {
            private $expectedRepositoryDir;
            private $lastStableVersion;

            public function __construct(string $expectedRepositoryDir, ?string $lastStableVersion)
            {
                parent::__construct();
                $this->expectedRepositoryDir = $expectedRepositoryDir;
                $this->lastStableVersion = $lastStableVersion;
            }

            public function getLastStableMinorVersion(string $repositoryDir, bool $readLocal = self::INCLUDE_LOCAL_BRANCHES, bool $readRemote = self::INCLUDE_REMOTE_BRANCHES): ?string
            {
                TestCase::assertTrue(method_exists(parent::class, __FUNCTION__), parent::class . ' no more has method ' . __FUNCTION__);
                TestCase::assertSame($this->expectedRepositoryDir, $repositoryDir);

                return $this->lastStableVersion;
            }

            public function getAllMinorVersions(
                string $repositoryDir,
                bool $readLocal = self::INCLUDE_LOCAL_BRANCHES,
                bool $readRemote = self::INCLUDE_REMOTE_BRANCHES
            ): array
            {
                TestCase::assertTrue(method_exists(parent::class, __FUNCTION__), parent::class . ' no more has method ' . __FUNCTION__);
                TestCase::assertSame($this->expectedRepositoryDir, $repositoryDir);

                return [$this->lastStableVersion];
            }

        };
    }

    /**
     * @test
     * @dataProvider provideLastStablePatchVersion
     * @param string|null $lastStablePatchVersion
     * @param string $lastUnstableVersion
     * @param string|null $expectedLastStablePatchVersion
     */
    public function I_can_get_last_stable_patch_version(
        ?string $lastStablePatchVersion,
        string $lastUnstableVersion,
        ?string $expectedLastStablePatchVersion
    ): void
    {
        $webVersions = new WebVersions(
            $this->createGitWithLastStablePatchVersion('some repository dir', $lastStablePatchVersion),
            'some repository dir',
            $lastUnstableVersion
        );
        self::assertSame($expectedLastStablePatchVersion, $webVersions->getLastStablePatchVersion());
    }

    public function provideLastStablePatchVersion(): array
    {
        return [
            'some patch version' => ['2.1.1', 'mistress', '2.1.1'],
            'only unstable version' => [null, 'mistress', null],
        ];
    }

    private function createGitWithLastStablePatchVersion(string $expectedRepositoryDir, ?string $lastPatchVersion): Git
    {
        return new class($expectedRepositoryDir, $lastPatchVersion) extends Git
        {
            private $expectedRepositoryDir;
            private $lastPatchVersion;

            public function __construct(string $expectedRepositoryDir, ?string $lastPatchVersion)
            {
                parent::__construct();
                $this->expectedRepositoryDir = $expectedRepositoryDir;
                $this->lastPatchVersion = $lastPatchVersion;
            }

            public function getLastPatchVersion(string $repositoryDir): ?string
            {
                TestCase::assertTrue(method_exists(parent::class, __FUNCTION__), parent::class . ' no more has method ' . __FUNCTION__);
                TestCase::assertSame($this->expectedRepositoryDir, $repositoryDir);

                return $this->lastPatchVersion;
            }

        };
    }

    /**
     * @test
     */
    public function I_can_get_all_stable_minor_versions(): void
    {
        $minorVersions = ['2.0', '1.2', '1.1', '1.0'];
        $webVersions = new WebVersions(
            $this->createGitWithAllMinorVersions('some repository dir', $minorVersions),
            'some repository dir',
            'mistress'
        );
        self::assertSame($minorVersions, $webVersions->getAllStableMinorVersions());
    }

    /**
     * @test
     */
    public function I_can_find_out_if_minor_version_exists(): void
    {
        $webVersions = new WebVersions(
            $this->createGitWithAllMinorVersions('some repository dir', $minorVersions = ['2.0', '1.2', '1.1', '1.0']),
            'some repository dir',
            'mistress'
        );
        foreach ($minorVersions as $minorVersion) {
            self::assertTrue($webVersions->hasMinorVersion($minorVersion));
        }
        self::assertTrue($webVersions->hasMinorVersion('mistress'));
        self::assertFalse($webVersions->hasMinorVersion('nonsense'));
    }

    /**
     * @test
     * @dataProvider provideSuperiorAndRelatedPatchVersions
     * @param string $superiorVersion
     * @param string $gitPatchVersion
     * @param string $expectedPatchVersion
     * @param string $lastUnstableVersion
     */
    public function I_can_get_last_patch_version_of_minor_or_major_version(
        string $superiorVersion,
        string $gitPatchVersion,
        string $expectedPatchVersion,
        string $lastUnstableVersion = 'mistress'
    ): void
    {
        $web = new WebVersions(
            $this->createGitWithLastPatchVersionOf('some repository dir', $superiorVersion, $gitPatchVersion),
            'some repository dir',
            $lastUnstableVersion
        );
        self::assertSame($expectedPatchVersion, $web->getLastPatchVersionOf($superiorVersion));
    }

    public function provideSuperiorAndRelatedPatchVersions(): array
    {
        return [
            'last unstable version' => ['mrs. mistress', 'mrs. mistress', 'mrs. mistress', 'mrs. mistress'],
            'minor version' => ['1.1', '1.1.14', '1.1.14'],
            'major version' => ['2', '2.6.41', '2.6.41'],
            'detached HEAD on specific commit' => ['(HEAD detached at c6a5ba1)', 'nonsense', '(HEAD detached at c6a5ba1)'],
            'detached head on specific tag version' => ['(HEAD detached at 1.0.25)', 'nonsense', '(HEAD detached at 1.0.25)'],
        ];
    }

    private function createGitWithLastPatchVersionOf(
        string $expectedRepositoryDir,
        string $expectedSuperiorVersion,
        string $lastPatchVersion
    ): Git
    {
        return new class($expectedRepositoryDir, $expectedSuperiorVersion, $lastPatchVersion) extends Git
        {
            private $expectedRepositoryDir;
            private $expectedSuperiorVersion;
            private $lastPatchVersion;

            public function __construct(
                string $expectedRepositoryDir,
                string $expectedSuperiorVersion,
                ?string $lastPatchVersion
            )
            {
                parent::__construct();
                $this->expectedRepositoryDir = $expectedRepositoryDir;
                $this->expectedSuperiorVersion = $expectedSuperiorVersion;
                $this->lastPatchVersion = $lastPatchVersion;
            }

            public function getLastPatchVersionOf(string $superiorVersion, string $repositoryDir): string
            {
                TestCase::assertTrue(method_exists(parent::class, __FUNCTION__), parent::class . ' no more has method ' . __FUNCTION__);
                TestCase::assertSame($this->expectedSuperiorVersion, $superiorVersion);
                TestCase::assertSame($this->expectedRepositoryDir, $repositoryDir);

                return $this->lastPatchVersion;
            }

        };
    }

    /**
     * @test
     */
    public function I_can_get_all_patch_versions(): void
    {
        $webVersions = new WebVersions(
            $this->createGitWithAllPatchVersions('some repository dir', ['2.0.5', '1.1.0', '1.0.976']),
            'some repository dir',
            'mistress'
        );
        self::assertSame(['mistress', '2.0.5', '1.1.0', '1.0.976'], $webVersions->getAllPatchVersions());
    }

    private function createGitWithAllPatchVersions(string $expectedRepositoryDir, array $mockPatchVersions): Git
    {
        return new class($expectedRepositoryDir, $mockPatchVersions) extends Git
        {
            private $expectedRepositoryDir;
            private $mockPatchVersions;

            public function __construct(string $expectedRepositoryDir, array $mockPatchVersions)
            {
                parent::__construct();
                $this->expectedRepositoryDir = $expectedRepositoryDir;
                $this->mockPatchVersions = $mockPatchVersions;
            }

            public function getAllPatchVersions(string $repositoryDir): array
            {
                TestCase::assertTrue(method_exists(parent::class, __FUNCTION__), parent::class . ' no more has method ' . __FUNCTION__);
                TestCase::assertSame($this->expectedRepositoryDir, $repositoryDir);

                return $this->mockPatchVersions;
            }

        };
    }

    /**
     * @test
     */
    public function I_can_ask_it_if_code_has_specific_minor_version(): void
    {
        $dir = 'some repository dir';
        $git = $this->createGitWithLastStableMinorVersion($dir, '123.456');
        $webVersions = new WebVersions($git, $dir);
        self::assertTrue($webVersions->hasMinorVersion($webVersions->getLastUnstableVersion()));
        self::assertTrue($webVersions->hasMinorVersion($webVersions->getLastStableMinorVersion()));
        self::assertFalse($webVersions->hasMinorVersion('-1'));
    }

    /**
     * @test
     */
    public function I_get_empty_array_as_a_list_of_stable_minor_versions_on_repo_without_versions()
    {
        $this->temporaryDir = sys_get_temp_dir() . '/' . uniqid('testing dir to get Git repo without stable minor versions ', true);
        if (!@mkdir($this->temporaryDir)) {
            self::fail("Can not create dir '$this->temporaryDir'");
        }
        $gitInit = new Process(['git', 'init'], $this->temporaryDir);
        $exitStatus = $gitInit->run();
        self::assertSame(0, $exitStatus, sprintf('Can not initialize testing Git repository: %s', $gitInit->getErrorOutput()));

        // intentionally a real Git repo to see if there is some exception surprise
        $webVersions = new WebVersions(new Git(), $this->temporaryDir);
        self::assertSame([], $webVersions->getAllStableMinorVersions());
    }

    /**
     * @test
     */
    public function I_get_empty_array_as_a_list_of_stable_patch_versions_on_repo_without_versions()
    {
        $this->temporaryDir = sys_get_temp_dir() . '/' . uniqid('testing dir to get Git repo without stable patch versions ', true);
        if (!@mkdir($this->temporaryDir)) {
            self::fail("Can not create dir '$this->temporaryDir'");
        }
        $gitInit = new Process(['git', 'init'], $this->temporaryDir);
        $exitStatus = $gitInit->run();
        self::assertSame(0, $exitStatus, sprintf('Can not initialize testing Git repository: %s', $gitInit->getErrorOutput()));

        // intentionally a real Git repo to see if there is some exception surprise
        $webVersions = new WebVersions(new Git(), $this->temporaryDir);
        self::assertSame([], $webVersions->getAllStablePatchVersions());
    }
}
