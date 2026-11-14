<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\Service;

use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;

final class ServiceNotFoundException extends RuntimeException implements NotFoundExceptionInterface {}
