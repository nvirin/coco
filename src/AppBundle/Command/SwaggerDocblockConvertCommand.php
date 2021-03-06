<?php

// src/AppBundle/Command/SwaggerDocblockConvertCommand.php
namespace AppBundle\Command;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Converts ApiDoc annotations to Swagger-PHP annotations.
 *
 * @author David Buchmann <david@liip.ch>
 * @author Guilhem Niot <guilhem.niot@gmail.com>
 */
class SwaggerDocblockConvertCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setDescription('')
            ->setName('api:doc:convert')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $extractor = $this->getContainer()->get('nelmio_api_doc.extractor.api_doc_extractor');
        $apiDocs = $extractor->extractAnnotations($extractor->getRoutes());

        foreach ($apiDocs as $annotation) {
            /** @var ApiDoc $apiDoc */
            $apiDoc = $annotation['annotation'];

            $refl = $extractor->getReflectionMethod($apiDoc->getRoute()->getDefault('_controller'));

            $this->rewriteClass($refl->getFileName(), $refl, $apiDoc);
        }
    }

    /**
     * Rewrite class with correct apidoc.
     */
    private function rewriteClass(string $path, \ReflectionMethod $method, ApiDoc $apiDoc)
    {
        echo "Processing $path::{$method->name}\n";
        $code = file_get_contents($path);
        $old = $this->locateNelmioAnnotation($code, $method->name);

        $code = substr_replace($code, $this->renderSwaggerAnnotation($apiDoc, $method), $old['start'], $old['length']);
        $code = str_replace('use Nelmio\ApiDocBundle\Annotation\ApiDoc;', "use Nelmio\ApiDocBundle\Annotation\Operation;\nuse Nelmio\ApiDocBundle\Annotation\Model;\nuse Swagger\Annotations as SWG;", $code);

        file_put_contents($path, $code);
    }

    private function renderSwaggerAnnotation(ApiDoc $apiDoc, \ReflectionMethod $method): string
    {
        $info = $apiDoc->toArray();
        if ($apiDoc->getResource()) {
            throw new \RuntimeException('implement me');
        }
        $path = str_replace('.{_format}', '', $apiDoc->getRoute()->getPath());

        $annotation = '@Operation(
     *     tags={"'.$apiDoc->getSection().'"},
     *     summary="'.$this->escapeQuotes($apiDoc->getDescription()).'"';

        foreach ($apiDoc->getFilters() as $name => $parameter) {
            $description = array_key_exists('description', $parameter)
                ? $this->escapeQuotes($parameter['description'])
                : 'todo';

            $annotation .= ',
     *     @SWG\Parameter(
     *         name="'.$name.'",
     *         in="query",
     *         description="'.$description.'",
     *         required='.(array_key_exists($name, $apiDoc->getRequirements()) ? 'true' : 'false').',
     *         type="'.$this->determineDataType($parameter).'"
     *     )';
        }

        // Put parameters for POST requests into formData, as Swagger cannot handle more than one body parameter
        $in = 'POST' === $apiDoc->getMethod()
            ? 'formData'
            : 'body';

        foreach ($apiDoc->getParameters() as $name => $parameter) {
            $description = array_key_exists('description', $parameter)
                ? $this->escapeQuotes($parameter['description'])
                : 'todo';

            $annotation .= ',
     *     @SWG\Parameter(
     *         name="'.$name.'",
     *         in="'.$in.'",
     *         description="'.$description.'",
     *         required='.(array_key_exists($name, $apiDoc->getRequirements()) ? 'true' : 'false').',
     *         type="'.$this->determineDataType($parameter).'"';

            if ('POST' !== $apiDoc->getMethod()) {
                $annotation .= ',
     *         schema=""';
            }

            $annotation .= '
     *     )';
        }

        if (array_key_exists('statusCodes', $info)) {
            $responses = $info['statusCodes'];
            foreach ($responses as $code => $description) {
                $responses[$code] = reset($description);
            }
        } else {
            $responses = [200 => 'Returned when successful'];
        }

        $responseMap = $apiDoc->getResponseMap();
        foreach ($responses as $code => $description) {
            $annotation .= ",
     *     @SWG\\Response(
     *         response=\"$code\",
     *         description=\"{$this->escapeQuotes($description)}\"";
            if (200 === $code && isset($responseMap[$code]['class'])) {
                $model = $responseMap[$code]['class'];
                $annotation .= ",
     *         @Model(type=\"$model\")";
            }
            $annotation .= '
     *     )';
        }

        $annotation .= '
     * )
     *';

        return $annotation;
    }

    /**
     * @return array with `start` position and `length`
     */
    private function locateNelmioAnnotation(string $code, string $methodName): array
    {
        $position = strpos($code, "tion $methodName(");
        if (false === $position) {
            throw new \RuntimeException("Method $methodName not found in controller.");
        }

        $docstart = strrpos(substr($code, 0, $position), '@ApiDoc');
        if (false === $docstart) {
            throw new \RuntimeException("Method $methodName has no @ApiDoc annotation around\n".substr($code, $position - 200, 150));
        }
        $docend = strpos($code, '* )', $docstart) + 3;

        return [
            'start' => $docstart,
            'length' => $docend - $docstart,
        ];
    }

    private function escapeQuotes(string $str): string
    {
        $lines = [];
        foreach (explode("\n", $str) as $line) {
            $lines[] = trim($line, ' *');
        }

        return str_replace('"', '""', implode(' ', $lines));
    }

    private function determineDataType(array $parameter): string
    {
        $dataType = isset($parameter['dataType']) ? $parameter['dataType'] : 'string';
        $transform = [
            'float' => 'number',
            'datetime' => 'string',
        ];
        if (array_key_exists($dataType, $transform)) {
            $dataType = $transform[$dataType];
        }

        return $dataType;
    }
}