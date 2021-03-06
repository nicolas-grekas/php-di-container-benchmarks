<?php

declare(strict_types=1);

namespace DiContainerBenchmarks\Benchmark;

use DiContainerBenchmarks\Container\ContainerInterface;
use DiContainerBenchmarks\OutputGenerator\OutputGeneratorInterface;
use DiContainerBenchmarks\Test\TestCase;
use DiContainerBenchmarks\Test\TestResult;
use DiContainerBenchmarks\TestSuite\TestSuiteInterface;

use function hrtime;

final class Benchmark
{
    private BenchmarkContextInterface $context;

    public function __construct(BenchmarkContextInterface $context)
    {
        $this->context = $context;
    }

    /**
     * @param ContainerInterface[] $containers
     */
    public function buildContainers(array $containers): void
    {
        echo "Building DI Containers...\n";
        foreach ($containers as $container) {
            echo "Building " . $container->getDisplayedName() . ": ";
            $t1 = hrtime(true);
            $container->build();
            $t2 = hrtime(true);

            echo (($t2 - $t1) / 1000000) . " ms\n";
        }
    }

    /**
     * @param TestSuiteInterface[] $testSuites
     * @param ContainerInterface[] $containers
     * @param OutputGeneratorInterface[] $outputGenerators
     */
    public function runBenchmark(array $testSuites, array $containers, array $outputGenerators): void
    {
        $benchmarkResult = new BenchmarkResult();

        foreach ($testSuites as $testSuite) {
            foreach ($testSuite->getTestCases() as $testCase) {
                foreach ($containers as $container) {
                    $this->runTest($testSuite, $testCase, $container, $benchmarkResult);
                }
            }
        }

        echo "Generating results...\n";
        foreach ($outputGenerators as $outputGenerator) {
            $outputGenerator->generateOutput($testSuites, $containers, $benchmarkResult);
        }

        echo "Benchmark finished successfully!\n";
    }

    private function runTest(
        TestSuiteInterface $testSuite,
        TestCase $testCase,
        ContainerInterface $container,
        BenchmarkResult $benchmarkResult
    ): void {
        $testSuiteNumber = $testSuite->getNumber();
        $testCaseNumber = $testCase->getNumber();
        $containerName = $container->getDisplayedName();

        echo "Running test $testSuiteNumber.$testCaseNumber: $containerName\n";

        $this->context->clear();
        for ($run = 0; $run < 30; $run++) {
            $containerNamespace = $container->getNamespace();

            $iterations = $testCase->getIterations();
            $testType = $testCase->getTestType();

            $output = $this->context->getTestOutput($testSuiteNumber, $containerNamespace, $iterations, $testType);
            $result = TestResult::createFromJson($output);
            $benchmarkResult->addTestResult($testSuite, $testCase, $container, $result);

            if ($result->isSuccessful() === false) {
                echo "Test failed: " . $result->getMessage() . "\n";
                break;
            }
        }
    }
}
