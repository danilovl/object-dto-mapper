<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\Security;

final class UntypedValueDto
{
    /**
     * @param mixed $value
     */
    public function __construct(public $value) {}
}
