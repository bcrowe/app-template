<?php

class Env {

/**
 * allByPrefix
 *
 * @param string $prefix
 * @param string $defaultKey
 * @return array
 */
	public static function allbyPrefix($prefix, $defaultKey = 'default') {
		if (!$prefix) {
			return [];
		}

		$values = $_ENV + $_SERVER;
		$keys = array_keys($values);

		$return = [];
		foreach($keys as $key) {
			if (strpos($key, $prefix) !== 0) {
				continue;
			}

			$val = $values[$key];

			if ($key === $prefix) {
				$key = $defaultKey;
			} else {
				$key = trim(substr($key, strlen($prefix)), '_');
			}

			$return[$key] = $val;
		}
		ksort($return, SORT_STRING | SORT_FLAG_CASE);

		return $return;
	}

/**
 * parseUrl
 *
 * @param string $value
 * @return array
 */
	public static function parseUrl($value) {
		$url = parse_url($value);
		if (!$url) {
			return false;
		}

		if (isset($url['query'])) {
			$extra = [];
			parse_str($url['query'], $extra);
			unset($url['query']);
			$url += $extra;
		}

		return $url;
	}

/**
 * parseCache
 *
 * @param array $defaults
 * @return array
 */
	public static function parseCache($defaults = [], $replacements = []) {
		$data = static::allByPrefix('CACHE_URL');
		if (!$data) {
			return false;
		}

		foreach($data as $key => $url) {
			$config = static::parseUrl($url);
			if (!$config) {
				continue;
			}


			$name = isset($config['name']) ? $config['name'] : strtolower(trim($key, '_'));
			$engine = isset($config['engine']) ? $config['engine'] : ucfirst(static::_get($config, 'scheme'));

			$config += [
				'engine' => $engine,
				'serialize' => ($engine === 'File'),
				'login' => static::_get($config, 'user'),
				'password' => static::_get($config, 'pass'),
				'server' => static::_get($config, 'host'),
				'servers' => static::_get($config, 'host')
			] + $defaults;

			$return[$name] = $config;
		}

		return static::_replace($return, $replacements);
	}

/**
 * parseDb
 *
 * @param array $defaults
 * @param array $replacements
 * @return array
 */
	public static function parseDb($defaults = [], $replacements = []) {
		$data = static::allByPrefix('DATABASE_URL');
		if (!$data) {
			return false;
		}

		foreach ($data as $key => $url) {
			$parsed = static::parseUrl($url);
			if (!$parsed) {
				continue;
			}

			$key = strtolower($key);
			$return[$key] = $parsed + [
				'datasource' => 'Database/' . ucfirst(strtolower($parsed['scheme'])),
				'persistent' => static::_get($parsed, 'persistent'),
				'host'       => static::_get($parsed, 'host'),
				'login'      => static::_get($parsed, 'user'),
				'password'   => static::_get($parsed, 'pass'),
				'database'   => substr($parsed['path'], 1),
				'persistent' => static::_get($parsed, 'persistent'),
				'encoding'   => static::_get($parsed, 'encoding') ?: 'utf8',
			] + $defaults;
		}

		return $return;
	}

/**
 * parseLogs
 *
 * @param array $defaults
 * @param array $replacements
 * @return array
 */
	public static function parseLogs($defaults = [], $replacements = []) {
		$data = static::allByPrefix('LOG_URL', 'debug');
		if (!$data) {
			return false;
		}

		if (defined('LOGS') && !isset($replacement['/LOGS/'])) {
			$replacements['/LOGS/'] = LOGS;
		}

		foreach($data as $key => $url) {
			$config = static::parseUrl($url);
			if (!$config) {
				continue;
			}


			$name = isset($config['name']) ? $config['name'] : strtolower(trim($key, '_'));
			$engine = isset($config['engine']) ? $config['engine'] : ucfirst(static::_get($config, 'scheme'));

			$config += [
				'engine' => $engine,
				'file' => $name
			] + $defaults;

			if (isset($config['types']) && !is_array($config['types'])) {
				$config['types'] = explode(',', $config['types']);
			}

			$return[$name] = $config;
		}

		return static::_replace($return, $replacements);
	}

/**
 * get a value out of an array if it exists
 *
 * @param array $data
 * @param string $key
 * @return mixed
 */
	protected static function _get($data, $key) {
		if (isset($data[$key])) {
			return $data[$key];
		}

		return null;
	}

/**
 * Recursively perform string replacements on array values
 *
 * @param array $data
 * @param array $replacements
 * @return array
 */
	protected static function _replace($data, $replacements) {
		if (!$replacements) {
			return $data;
		}

		foreach($data as &$value) {
			$value = str_replace(array_keys($replacements), array_values($replacements), $value);
			if (is_array($value)) {
				$value = static::_replace($value, $replacements);
			}
		}

		return $data;
	}
}
