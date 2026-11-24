<?php
declare(strict_types=1);

class GaussianNaiveBayes
{
    private array $classes = [];
    private array $stats = [];
    private array $priors = [];
    private const EPSILON = 1.0e-9;

    public function train(array $rows, array $features, string $target): void
    {
        $groups = [];
        foreach ($rows as $row) {
            $class = (string)($row[$target] ?? '');
            if ($class === '') continue;
            $groups[$class][] = $row;
        }
        $total = array_sum(array_map('count', $groups));
        foreach ($groups as $class => $items) {
            $this->classes[] = $class;
            $this->priors[$class] = count($items) / max(1, $total);
            foreach ($features as $feature) {
                $values = array_map(static fn($item): float => (float)($item[$feature] ?? 0), $items);
                $mean = array_sum($values) / max(1, count($values));
                $variance = array_sum(array_map(static fn(float $value): float => ($value - $mean) ** 2, $values)) / max(1, count($values));
                $this->stats[$class][$feature] = ['mean' => $mean, 'variance' => max($variance, self::EPSILON)];
            }
        }
    }

    public function predict(array $features): array
    {
        $logPosteriors = [];
        foreach ($this->classes as $class) {
            $log = log(max($this->priors[$class], self::EPSILON));
            foreach ($features as $feature => $value) {
                $stat = $this->stats[$class][$feature] ?? ['mean' => 0, 'variance' => 1];
                $variance = max($stat['variance'], self::EPSILON);
                $log += -0.5 * log(2 * M_PI * $variance) - (($value - $stat['mean']) ** 2) / (2 * $variance);
            }
            $logPosteriors[$class] = $log;
        }
        if (!$logPosteriors) return ['class' => null, 'probability' => 0, 'ranking' => []];
        $max = max($logPosteriors);
        $weights = array_map(static fn(float $value): float => exp($value - $max), $logPosteriors);
        $sum = array_sum($weights);
        $probabilities = [];
        foreach ($weights as $class => $weight) $probabilities[$class] = $weight / max($sum, self::EPSILON);
        arsort($probabilities);
        $class = array_key_first($probabilities);
        return ['class' => $class, 'probability' => $probabilities[$class] ?? 0, 'ranking' => $probabilities];
    }

    public function toArray(): array { return ['classes' => $this->classes, 'stats' => $this->stats, 'priors' => $this->priors]; }
    public function fromArray(array $data): void { $this->classes = $data['classes'] ?? []; $this->stats = $data['stats'] ?? []; $this->priors = $data['priors'] ?? []; }
}
?>