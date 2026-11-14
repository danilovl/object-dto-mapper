<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\Conversion;

final class EnumEntity
{
    public Status $status = Status::Active;

    public Priority $priority = Priority::High;

    public Suit $suit = Suit::Hearts;

    public string $statusCode = 'blocked';

    public int $priorityCode = 1;

    public string $priorityString = '2';

    public ?string $nullableCode = null;

    public string $invalidCode = 'unknown';
}
