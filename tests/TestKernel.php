<?php

namespace Tourze\AttendanceManageBundle\Tests;

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use SymfonyTestingFramework\Kernel;
use Tourze\AttendanceManageBundle\AttendanceManageBundle;

/**
 * @internal
 * @coversNothing
 * @phpstan-ignore-next-line forbiddenExtendOfNonAbstractClass
 */
class TestKernel extends Kernel
{
    public function __construct()
    {
        parent::__construct(
            'test',
            true,
            __DIR__ . '/../',
            [AttendanceManageBundle::class => ['all' => true]]
        );
    }

    protected function build(ContainerBuilder $container): void
    {
        parent::build($container);

        // 手动注册我们的测试实体映射
        $container->prependExtensionConfig('doctrine', [
            'orm' => [
                'mappings' => [
                    'AttendanceManageBundle' => [
                        'type' => 'attribute',
                        'is_bundle' => true,
                        'prefix' => 'Tourze\AttendanceManageBundle\Entity',
                        'alias' => 'AttendanceManageBundle',
                    ],
                ],
            ],
        ]);

        // 禁用可能引起问题的配置
        $container->prependExtensionConfig('framework', [
            'test' => true,
            'session' => [
                'storage_factory_id' => 'session.storage.factory.mock_file',
            ],
        ]);
    }
}
