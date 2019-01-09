<?php

namespace splitbrain\Tests\JiraDash\CLI;

use splitbrain\JiraDash\CLI\Update;
use splitbrain\Tests\JiraDash\TestCase;

class UpdateTest extends TestCase
{

    function providerParseOfferEstimate()
    {
        return [
            ['EstImaTed: 7.5D', 216000],
            ['Estimated: 7.5d', 216000],
            ['Estimated:7.5d', 216000],
            ['Estimate: 7.5d', 216000],
            ['Estimate:7.5d', 216000],
            ['Est: 7.5d', 216000],
            ['Est:7.5d', 216000],

            ['EstImaTed: 7,5D', 216000],
            ['Estimated: 7,5d', 216000],
            ['Estimated:7,5d', 216000],
            ['Estimate: 7,5d', 216000],
            ['Estimate:7,5d', 216000],
            ['Est: 7,5d', 216000],
            ['Est:7,5d', 216000],

            ['Estimated: 7.5m', 450],
            ['Estimated: 7.5h', 27000],
        ];
    }

    /**
     * @param $in
     * @param $out
     * @dataProvider providerParseOfferEstimate
     */
    function testParseOfferEstimate($in, $out)
    {
        $this->assertSame($out, Update::parseOfferEstimate($in));
    }

}
