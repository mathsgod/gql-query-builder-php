<?php

namespace GQLQueryBuilder;

class DefaultSubscriptionAdapter
{

    private $variables;
    private $fields;
    private $operation;

    public function __construct(array $options)
    {

        if (array_is_list($options)) {
            $this->variables = Utils::resolveVariables($options);
        } else {
            if (isset($options['variables'])) {
                $this->variables = $options['variables'];
            }

            $this->fields = $options['fields'] ?? [];
            $this->operation = $options['operation'];
        }
    }

    public function subscriptionBuilder()
    {
        return $this->operationWrapperTemplate(
            $this->operationTemplate($this->variables)
        );
    }

    public function subscriptionsBuilder($queries)
    {
        $tmp = [];

        foreach ($queries as $query) {
            if ($query) {
                $this->operation = $query['operation'];
                if (isset($query['fields'])) {
                    $this->fields = $query['fields'];
                }

                if (isset($query["variables"])) {
                    $this->variables = $query['variables'];
                }


                $tmp[] = $this->operationTemplate();
            }
        }

        return $this->operationWrapperTemplate(implode(", ", $tmp));
    }

    private function operationWrapperTemplate(string $content)
    {
        $query = "subscription";
        $query .= $this->queryDataArgumentAndTypeMap() . " { " . $content . "}";

        return [
            "query" => $query,
            "variables" => Utils::queryVariablesMap($this->variables, $this->fields),
        ];
    }

    private function queryDataArgumentAndTypeMap(): string
    {
        $variablesUsed = $this->variables ?? [];


        if ($this->fields && is_array($this->fields)) {
            $variablesUsed = array_merge($variablesUsed, Utils::getNestedVariables($this->fields));
        }
        if (count($variablesUsed) > 0) {

            $s = [];
            foreach ($variablesUsed as $key => $value) {
                $s[] = '$' . $key . ': ' . Utils::queryDataType($value);
            }
            return '(' . implode(', ', $s) . ')';
        } else {
            return '';
        }
    }

    private function operationTemplate(?array $variables = null)
    {
        $operation = is_string($this->operation) ? $this->operation : $this->operation['alias'] . ': ' . $this->operation['name'];

        return $operation . ($variables ? Utils::queryDataNameAndArgumentMap($variables) : '') . ($this->fields && count($this->fields) > 0 ? ' { ' . Utils::queryFieldsMap($this->fields) . ' } ' : '');
    }
}
