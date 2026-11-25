<?php
class AnswerGraphTestCase {
    public $assertions = 0;
    public function assertTrue($condition, $msg = '') {
        $this->assertions++;
        if (!$condition) throw new Exception($msg ?: "Failed asserting that condition is true.");
    }
    public function assertEquals($expected, $actual, $msg = '') {
        $this->assertions++;
        if ($expected !== $actual) throw new Exception($msg ?: "Expected: " . var_export($expected, true) . ", got: " . var_export($actual, true));
    }
    public function assertStringContainsString($needle, $haystack, $msg = '') {
        $this->assertions++;
        if (strpos($haystack, $needle) === false) throw new Exception($msg ?: "Failed asserting that '$haystack' contains '$needle'.");
    }
    public function assertMatchesRegularExpression($pattern, $string, $msg = '') {
        $this->assertions++;
        if (!preg_match($pattern, $string)) throw new Exception($msg ?: "Failed asserting that string matches pattern $pattern.");
    }
    public function assertGreaterThanOrEqual($expected, $actual, $msg = '') {
        $this->assertions++;
        if ($actual < $expected) throw new Exception($msg ?: "Failed asserting that $actual >= $expected.");
    }
    public function assertGreaterThan($expected, $actual, $msg = '') {
        $this->assertions++;
        if ($actual <= $expected) throw new Exception($msg ?: "Failed asserting that $actual > $expected.");
    }
    public function assertArrayHasKey($key, $array, $msg = '') {
        $this->assertions++;
        if (!array_key_exists($key, $array)) throw new Exception($msg ?: "Failed asserting that array has key '$key'.");
    }
    public function assertNotEmpty($value, $msg = '') {
        $this->assertions++;
        if (empty($value)) throw new Exception($msg ?: "Failed asserting that value is not empty.");
    }
}

function run_test_file($file) {
    require_once $file;
    foreach (get_declared_classes() as $class) {
        if (is_subclass_of($class, 'AnswerGraphTestCase')) {
            $instance = new $class();
            $count = 0;
            foreach (get_class_methods($instance) as $method) {
                if (strpos($method, 'test_') === 0) {
                    echo "Running $class::$method... ";
                    try {
                        $instance->$method();
                        echo "PASS\n";
                        $count++;
                    } catch (Exception $e) {
                        echo "FAIL: " . $e->getMessage() . "\n";
                        exit(1);
                    }
                }
            }
            echo "OK ($count tests passed, {$instance->assertions} assertions)\n";
            return;
        }
    }
}
