<?php

namespace AppSignal\Tests\Integration\laravel\tests\Feature;

use OpenTelemetry\SDK\Metrics\Data\HistogramDataPoint;
use OpenTelemetry\SDK\Metrics\Data\Sum;
use Tests\TestCase;

class MetricsTest extends TestCase
{
    public function test_set_gauge(): void
    {
        $this->get('/set-gauge');

        [$rootSpan] = $this->getSpans();
        [$metric1, $metric2] = $this->getMetrics();

        $this->assertEquals('my_gauge', $metric1->name);

        $dataPoints = $metric1->data->dataPoints;
        $this->assertCount(1, $dataPoints);
        $this->assertEquals($rootSpan->getTraceId(), $dataPoints[0]->exemplars[0]->traceId);
        $this->assertEquals($rootSpan->getSpanId(), $dataPoints[0]->exemplars[0]->spanId);

        $this->assertEquals('my_gauge_with_attributes', $metric2->name);

        $dataPoints = $metric2->data->dataPoints;
        $this->assertCount(1, $dataPoints);
        $this->assertEquals("eu", $dataPoints[0]->attributes->get("region"));
    }

    public function test_add_distribution_value(): void
    {
        $this->get('/add-distribution-values');

        [$rootSpan] = $this->getSpans();
        [$metric, $metricWithAttributes] = $this->getMetrics();

        $this->assertEquals('memory_usage', $metric->name);

        $histogram = $metric->data->dataPoints[0];
        $this->assertInstanceOf(HistogramDataPoint::class, $histogram);
        $this->assertEquals(2, $histogram->count);
        $this->assertEquals(120, $histogram->sum);

        [$firstSample, $secondSample] = $histogram->exemplars;
        $this->assertEquals(50, $firstSample->value);
        $this->assertEquals($rootSpan->getTraceId(), $firstSample->traceId);

        $this->assertEquals(70, $secondSample->value);
        $this->assertEquals($rootSpan->getTraceId(), $secondSample->traceId);


        $this->assertEquals('with_attributes', $metricWithAttributes->name);
        $histogram = $metricWithAttributes->data->dataPoints[0];
        $this->assertEquals(3, $histogram->count);
        $this->assertEquals(60, $histogram->sum);
        $this->assertEquals("eu", $histogram->attributes->get("region"));

        [$firstSample, $secondSample, $thirdSample] = $histogram->exemplars;
        $this->assertEquals(10, $firstSample->value);
        $this->assertEquals(20, $secondSample->value);
        $this->assertEquals(30, $thirdSample->value);
    }

    public function test_increment_counter(): void
    {
        $this->get('/counter');

        [$metric] = $this->getMetrics();

        $this->assertEquals('my_counter', $metric->name);

        $sum = $metric->data;
        $this->assertInstanceOf(Sum::class, $sum);
        $this->assertCount(2, $sum->dataPoints);

        [$first, $second] = $sum->dataPoints;
        $this->assertEquals(1, $first->value);
        $this->assertEmpty($first->attributes->toArray());

        $this->assertEquals(3, $second->value);
        $this->assertEquals("eu", $second->attributes->get('region'));
    }
}
