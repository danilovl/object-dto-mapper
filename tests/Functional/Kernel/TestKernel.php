<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Functional\Kernel;

use Danilovl\ObjectDtoMapper\ObjectDtoMapperBundle;
use Danilovl\ObjectDtoMapper\Tests\Mock\Service\{
    EmailNotEmptyMapIfService,
    UppercaseMapTransformService
};
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;

final class TestKernel extends Kernel
{
    use MicroKernelTrait;

    /**
     * @param array<string, mixed> $bundleConfig
     */
    public function __construct(
        string $environment,
        bool $debug,
        private readonly array $bundleConfig = []
    ) {
        parent::__construct($environment, $debug);
    }

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle;
        yield new ObjectDtoMapperBundle;
    }

    protected function configureContainer(ContainerConfigurator $configurator): void
    {
        $configurator->extension('framework', [
            'test' => true,
            'secret' => 'test',
            'http_method_override' => false,
            'handle_all_throwables' => true,
            'php_errors' => ['log' => true],
            'property_access' => ['enabled' => true],
        ]);

        if (count($this->bundleConfig) > 0) {
            $configurator->extension('danilovl_object_dto_mapper', $this->bundleConfig);
        }

        $services = $configurator->services()
            ->defaults()
            ->autowire()
            ->autoconfigure(false)
            ->private();

        $services->set(EmailNotEmptyMapIfService::class);
        $services->set(UppercaseMapTransformService::class);
    }

    protected function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new class() implements CompilerPassInterface {
            public function process(ContainerBuilder $container): void
            {
                // trusted proxies/headers stay as env placeholders in a non-dumped container
                foreach (['kernel.trusted_proxies', 'kernel.trusted_headers'] as $parameter) {
                    if ($container->hasParameter($parameter)) {
                        $container->setParameter($parameter, null);
                    }
                }
            }
        });
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir() . '/object-dto-mapper-bundle/' . $this->getCacheKey() . '/cache';
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir() . '/object-dto-mapper-bundle/' . $this->getCacheKey() . '/log';
    }

    protected function initializeContainer(): void
    {
        $container = $this->buildContainer();
        // normally set while dumping the container, required by the cache pools of property_accessor
        $container->setParameter('container.build_id', 'test');
        $container->compile();
        $container->set('kernel', $this);
        $this->container = $container;
    }

    private function getCacheKey(): string
    {
        return md5(serialize($this->bundleConfig)) . '_' . $this->environment;
    }
}
