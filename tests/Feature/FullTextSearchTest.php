<?php

use App\Support\FullTextSearch;

it('does not use the ngram index on databases without the ngram parser', function (): void {
    expect(FullTextSearch::supportsNgram())->toBeFalse();
});
