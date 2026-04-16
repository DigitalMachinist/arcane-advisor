<?php

declare(strict_types=1);

test('.env.example sets LLM_DRIVER=fixture', function (): void {
    $contents = (string) file_get_contents(base_path('.env.example'));

    expect($contents)->toMatch('/^LLM_DRIVER=fixture\s*$/m');
});
