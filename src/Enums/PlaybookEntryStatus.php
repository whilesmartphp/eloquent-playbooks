<?php

namespace Whilesmart\Playbooks\Enums;

enum PlaybookEntryStatus: string
{
    // Drafted by the extractor; waits for a person to accept it.
    case Suggested = 'suggested';

    // Written or accepted by a person; grounds prompts.
    case Confirmed = 'confirmed';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
