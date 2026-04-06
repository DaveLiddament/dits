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

        $indexXml = $this->loadXml($indexPath);
        $indexXml->registerXPathNamespace('p', 'https://schema.phpunit.de/coverage/1.0');

        /** @var array<string, list<LineCoverage>> $testLineCoverages */
        $testLineCoverages = [];

        $fileElements = $this->xpath($indexXml, '//p:project/p:directory/p:file');

        foreach ($fileElements as $fileElement) {
            /** @infection-ignore-all Equivalent mutant: SimpleXMLElement attribute is coerced to string by all downstream uses */
            $href = (string) $fileElement['href'];
            /** @infection-ignore-all Defensive guard: PHPUnit always emits href, only fails on hand-crafted malformed XML */
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
        $fileXml = $this->loadXml($filePath);
        $fileXml->registerXPathNamespace('p', 'https://schema.phpunit.de/coverage/1.0');

        $fileElements = $this->xpath($fileXml, '//p:file');
        /** @infection-ignore-all Defensive guard: per-file XML always has exactly one <file> element */
        Assert::count($fileElements, 1);

        /** @infection-ignore-all Equivalent mutant: SimpleXMLElement attribute is coerced to string by string concatenation below */
        $name = (string) $fileElements[0]['name'];
        /** @infection-ignore-all Equivalent mutant: SimpleXMLElement attribute is coerced to string by ltrim() below */
        $path = (string) $fileElements[0]['path'];
        /** @infection-ignore-all Defensive guard: PHPUnit always emits name attribute */
        Assert::notEmpty($name);

        $relativePath = $sourcePrefix.ltrim($path, '/').$name;

        $coveredByElements = $this->xpath($fileXml, '//p:file/p:coverage/p:line/p:covered');

        foreach ($coveredByElements as $coveredBy) {
            $testNameString = (string) $coveredBy['by'];
            /** @infection-ignore-all Defensive guard: PHPUnit always emits by attribute */
            Assert::notEmpty($testNameString);

            $parentLine = $this->xpath($coveredBy, '..');
            /** @infection-ignore-all Defensive guard: xpath '..' on a child always returns the parent */
            Assert::notEmpty($parentLine);
            $lineNumber = (int) $parentLine[0]['nr'];
            /** @infection-ignore-all Defensive guard: PHPUnit emits positive line numbers */
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

        /** @infection-ignore-all Equivalent mutant: SimpleXMLElement::xpath returns sequentially-keyed arrays already */
        return \is_array($result) ? array_values($result) : [];
    }

    private function readFile(string $path): string
    {
        $content = file_get_contents($path);
        /** @infection-ignore-all Defensive guard: file existence is checked by caller (Assert::fileExists in parse()) */
        Assert::notFalse($content, sprintf('Failed to read file: %s', $path));

        return $content;
    }

    /**
     * @infection-ignore-all This method is error-handling/cleanup glue. Mutations to libxml state restoration,
     *                       error message formatting, exception codes, and try/finally structure are all
     *                       equivalent — they don't affect the observable outcome of parse() failing on
     *                       malformed XML, which IS tested via the corrupt-* fixtures.
     */
    private function loadXml(string $path): \SimpleXMLElement
    {
        $content = $this->readFile($path);

        $previousUseInternalErrors = libxml_use_internal_errors(true);

        try {
            $xml = new \SimpleXMLElement($content);
        } catch (\Throwable $e) {
            $errors = libxml_get_errors();
            libxml_clear_errors();
            $errorMessage = '' !== $e->getMessage() ? $e->getMessage() : (isset($errors[0]) ? trim($errors[0]->message) : 'unknown error');

            throw new \InvalidArgumentException(sprintf('Failed to parse coverage XML file %s: %s', $path, $errorMessage), 0, $e);
        } finally {
            libxml_use_internal_errors($previousUseInternalErrors);
        }

        return $xml;
    }
}
