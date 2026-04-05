<?php

declare(strict_types=1);

namespace DaveLiddament\TestSelector\CoverageParser;

use DaveLiddament\TestSelector\Coverage\CommitIdentifier;
use DaveLiddament\TestSelector\Coverage\LineCoverage;
use DaveLiddament\TestSelector\Coverage\TestCoverage;
use DaveLiddament\TestSelector\Coverage\TestCoverageReport;
use DaveLiddament\TestSelector\Coverage\TestName;
use Webmozart\Assert\Assert;

final readonly class PhpUnitCoverageXmlParser
{
    /**
     * Parses a PHPUnit --coverage-xml output directory into a TestCoverageReport.
     *
     * @param string $coverageXmlDir path to the directory containing index.xml and per-file XMLs
     * @param string $sourcePrefix   source directory relative to project root (e.g. "src/")
     */
    public function parse(string $coverageXmlDir, CommitIdentifier $commitIdentifier, string $sourcePrefix): TestCoverageReport
    {
        $indexPath = $coverageXmlDir.'/index.xml';
        Assert::fileExists($indexPath, 'Coverage XML directory must contain index.xml');

        $indexXml = new \SimpleXMLElement($this->readFile($indexPath));
        $indexXml->registerXPathNamespace('p', 'https://schema.phpunit.de/coverage/1.0');

        /** @var array<string, list<LineCoverage>> $testLineCoverages */
        $testLineCoverages = [];

        $fileElements = $this->xpath($indexXml, '//p:project/p:directory/p:file');

        foreach ($fileElements as $fileElement) {
            $href = (string) $fileElement['href'];
            Assert::notEmpty($href);

            $filePath = $coverageXmlDir.'/'.$href;
            if (!file_exists($filePath)) {
                continue;
            }

            $this->parseFileXml($filePath, $sourcePrefix, $testLineCoverages);
        }

        $testCoverages = [];
        foreach ($testLineCoverages as $testNameString => $lineCoverages) {
            $testCoverages[] = new TestCoverage(
                new TestName($testNameString),
                ...$lineCoverages,
            );
        }

        return new TestCoverageReport($commitIdentifier, ...$testCoverages);
    }

    /**
     * @param array<string, list<LineCoverage>> $testLineCoverages
     */
    private function parseFileXml(string $filePath, string $sourcePrefix, array &$testLineCoverages): void
    {
        $fileXml = new \SimpleXMLElement($this->readFile($filePath));
        $fileXml->registerXPathNamespace('p', 'https://schema.phpunit.de/coverage/1.0');

        $fileElements = $this->xpath($fileXml, '//p:file');
        Assert::count($fileElements, 1);

        $name = (string) $fileElements[0]['name'];
        $path = (string) $fileElements[0]['path'];
        Assert::notEmpty($name);

        $relativePath = $sourcePrefix.ltrim($path, '/').$name;

        $coveredByElements = $this->xpath($fileXml, '//p:file/p:coverage/p:line/p:covered');

        foreach ($coveredByElements as $coveredBy) {
            $testNameString = (string) $coveredBy['by'];
            Assert::notEmpty($testNameString);

            $parentLine = $this->xpath($coveredBy, '..');
            Assert::notEmpty($parentLine);
            $lineNumber = (int) $parentLine[0]['nr'];
            Assert::positiveInteger($lineNumber);

            $testLineCoverages[$testNameString][] = new LineCoverage($relativePath, $lineNumber);
        }
    }

    /**
     * @return list<\SimpleXMLElement>
     */
    private function xpath(\SimpleXMLElement $xml, string $expression): array
    {
        $result = $xml->xpath($expression);

        return \is_array($result) ? array_values($result) : [];
    }

    private function readFile(string $path): string
    {
        $content = file_get_contents($path);
        Assert::notFalse($content, sprintf('Failed to read file: %s', $path));

        return $content;
    }
}
