<?php

namespace Sidekicker\FlagrFeature;

use Flagr\Client\Api\EvaluationApi;
use Flagr\Client\ApiException;
use Flagr\Client\Model\EvaluationBatchRequest;
use Illuminate\Config\Repository;

class Feature
{
    /**
     * @var array<mixed>
     */
    private array $context = [];

    /**
     * @var array<mixed>
     */
    private array $evaluationResults = [];

    public function __construct(private EvaluationApi $evaluator, private Repository $config)
    {
    }

    /**
     * @param array<mixed> $context
     * @return self
     */
    public function setContext(array $context): self
    {
        //reset results if context has changed
        $this->evaluationResults = [];
        $this->context = $context;

        return $this;
    }


    /**
     * @param array $context
     * @return self
     */
    public function addContext(array $context): self
    {
        $this->setContext(array_merge($this->context, $context));

        return $this;
    }

    /**
     * @param string $flag
     * @param array<mixed> $matchAttachment
     * @param string $matchVariant
     * @return boolean
     */
    public function match(string $flag, ?array &$matchAttachment = null, string $matchVariant = 'on'): bool
    {
        $match = false;
        $matchAttachment = null;

        $this->evaluate(
            $flag,
            ...[$matchVariant => function (?array $attachment) use (&$match, &$matchAttachment) {
                $match = true;
                $matchAttachment = $attachment;
            }]
        );

        return $match;
    }

    /**
     * @param string $flag
     * @param callable ...$callbacks
     *
     * @return void
     */
    public function evaluate(string $flag, callable ...$callbacks): void
    {
        [$variantKey, $attachment] = $this->performEvaluation($flag);

        $callback = $callbacks[$variantKey]
            ?? $callbacks['otherwise']
            ?? fn (?array $attachment) => false;

        $callback($attachment);
    }

    /**
     * @param string $flag
     * @return array<mixed>
     */
    private function performEvaluation(string $flag): array
    {
        if (!isset($this->evaluationResults[$flag])) {
            $this->evaluationResults = [];
            $evaluationBatchRequest = new EvaluationBatchRequest();
            if (is_array($this->config->get('flagr-feature.tags')) && count($this->config->get('flagr-feature.tags')) > 0) {
                $evaluationBatchRequest->setFlagTags($this->config->get('flagr-feature.tags'));
                $evaluationBatchRequest->setFlagTagsOperator($this->config->get('flagr-feature.tag_operator', 'ANY'));
            } else {
                $evaluationBatchRequest->setFlagKeys([$flag]);
            }
            $evaluationBatchRequest->setEntities([$this->context]);

            try {

                $results = $this->evaluator->postEvaluationBatch($evaluationBatchRequest)->getEvaluationResults() ?? [];

                foreach ($results as $evaluationResult) {
                    $this->evaluationResults[$evaluationResult->getFlagKey()] = [
                        $evaluationResult->getVariantKey(),
                        $evaluationResult->getVariantAttachment()
                    ];
                }
            } catch (ApiException $e) {
                //
            }
        }

        // Only attempt to evaluate a flag once per instantiation
        $this->evaluationResults[$flag] = $this->evaluationResults[$flag] ?? ['', null];

        return $this->evaluationResults[$flag];
    }
}
