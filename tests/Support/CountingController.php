<?php

namespace Tests\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Whilesmart\Playbooks\Http\Controllers\PlaybookEntryController;
use Whilesmart\Playbooks\Models\PlaybookEntry;

class CountingController extends PlaybookEntryController
{
    public static int $shows = 0;

    public function show(Request $request, PlaybookEntry $playbook_entry): JsonResponse
    {
        static::$shows++;

        return parent::show($request, $playbook_entry);
    }
}
