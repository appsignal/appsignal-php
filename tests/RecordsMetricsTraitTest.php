<?php

namespace AppSignal\Tests;

use AppSignal\RecordsMetrics;

class RecordsMetricsTraitTest extends OpenTelemetryTestCase
{
    public function testSetGaugeRecordsGaugeMetric(): void
    {
        AppSignalMetricsStub::setGauge('cpu_usage', 75.5);

        $metric = $this->findMetricByName('cpu_usage');
        $this->assertNotNull($metric, 'Gauge metric not found');

        $dataPoints = $metric->data->dataPoints; // @phpstan-ignore property.notFound
        $this->assertCount(1, $dataPoints);
        $this->assertEquals(75.5, $dataPoints[0]->value);
    }

    public function testSetGaugeWithIntegerValue(): void
    {
        AppSignalMetricsStub::setGauge('active_connections', 42);

        $metric = $this->findMetricByName('active_connections');
        $this->assertNotNull($metric);

        $dataPoints = $metric->data->dataPoints; // @phpstan-ignore property.notFound
        $this->assertEquals(42, $dataPoints[0]->value);
    }

    public function testAddDistributionValueRecordsHistogramMetric(): void
    {
        AppSignalMetricsStub::addDistributionValue('request_duration', 0.250);

        $metric = $this->findMetricByName('request_duration');
        $this->assertNotNull($metric, 'Histogram metric "request_duration" not found');
        $this->assertEquals('request_duration', $metric->name);

        $dataPoints = $metric->data->dataPoints; // @phpstan-ignore property.notFound
        $this->assertCount(1, $dataPoints);
        $this->assertEquals(1, $dataPoints[0]->count);
        $this->assertEquals(0.250, $dataPoints[0]->sum);
    }

    public function testAddDistributionValueMultipleTimes(): void
    {
        AppSignalMetricsStub::addDistributionValue('response_time', 0.100);
        AppSignalMetricsStub::addDistributionValue('response_time', 0.200);
        AppSignalMetricsStub::addDistributionValue('response_time', 0.300);

        $metric = $this->findMetricByName('response_time');
        $this->assertNotNull($metric);

        $dataPoints = $metric->data->dataPoints; // @phpstan-ignore property.notFound
        $this->assertCount(1, $dataPoints);
        $this->assertEquals(3, $dataPoints[0]->count);
        $this->assertEqualsWithDelta(0.600, $dataPoints[0]->sum, 0.0001);
    }

    public function testIncrementCounterRecordsCounterMetric(): void
    {
        AppSignalMetricsStub::incrementCounter('http_requests', 1);

        $metric = $this->findMetricByName('http_requests');
        $this->assertNotNull($metric, 'Counter metric "http_requests" not found');
        $this->assertEquals('http_requests', $metric->name);

        $dataPoints = $metric->data->dataPoints; // @phpstan-ignore property.notFound
        $this->assertCount(1, $dataPoints);
        $this->assertEquals(1, $dataPoints[0]->value);
    }

    public function testIncrementCounterAccumulatesValues(): void
    {
        AppSignalMetricsStub::incrementCounter('page_views', 5);
        AppSignalMetricsStub::incrementCounter('page_views', 3);

        $metric = $this->findMetricByName('page_views');
        $this->assertNotNull($metric);

        $dataPoints = $metric->data->dataPoints; // @phpstan-ignore property.notFound
        $this->assertCount(1, $dataPoints);
        $this->assertEquals(8, $dataPoints[0]->value);
    }

    public function testDifferentMetricNamesAreIndependent(): void
    {
        AppSignalMetricsStub::incrementCounter('counter_a', 10);
        AppSignalMetricsStub::incrementCounter('counter_b', 20);


        $metricA = $this->findMetricByName('counter_a');
        $metricB = $this->findMetricByName('counter_b');

        $this->assertNotNull($metricA);
        $this->assertNotNull($metricB);

        $this->assertEquals(10, $metricA->data->dataPoints[0]->value); // @phpstan-ignore property.notFound
        $this->assertEquals(20, $metricB->data->dataPoints[0]->value); // @phpstan-ignore property.notFound
    }
}

class AppSignalMetricsStub
{
    use RecordsMetrics;
}
