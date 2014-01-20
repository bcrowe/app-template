<?php

class Env {

/**
 * parseCacheUrl
 *
 * @param string $url
 * @param array $defaults
 * @param array $replacements
 * @return array
 */
	public static function parseCacheUrl($url, $defaults = [], $replacements = []) {
		if (!$url) {
			return false;
		}

		$return = static::parseUrl($url);
		if (!$return) {
			return false;
		}

		$engine = isset($return['engine']) ? $return['engine'] : ucfirst(static::_get($return, 'scheme'));

		$return += [
			'engine' => $engine,
			'serialize' => ($engine === 'File'),
			'login' => static::_get($return, 'user'),
			'password' => static::_get($return, 'pass'),
			'server' => static::_get($return, 'host'),
			'servers' => static::_get($return, 'host')
		] + $defaults;

		return static::_replace($return, $replacements);
	}

/**
 * parseDbUrl
 *
 * @param string $url
 * @param array $defaults
 * @param array $replacements
 * @return array
 */
	public static function parseDbUrl($url, $defaults = [], $replacements = []) {
		if (!$url) {
			return false;
		}

		$return = static::parseUrl($url);
		if (!$return) {
			return false;
		}

		$return += [
			'datasource' => 'Database/' . ucfirst(strtolower($return['scheme'])),
			'persistent' => static::_get($return, 'persistent'),
			'host' => static::_get($return, 'host'),
			'login' => static::_get($return, 'user'),
			'password' => static::_get($return, 'pass'),
			'database' => substr($return['path'], 1),
			'persistent' => static::_get($return, 'persistent'),
			'encoding' => static::_get($return, 'encoding') ?: 'utf8'
		] + $defaults;

		return static::_replace($return, $replacements);
	}

/**
 * parseLogs
 *
 * @param string $url
 * @param array $defaults
 * @param array $replacements
 * @return array
 */
	public static function parseLogUrl($url, $defaults = [], $replacements = []) {
		if (!$url) {
			return false;
		}

		$return = static::parseUrl($url);
		if (!$return) {
			return false;
		}

		$engine = isset($return['engine']) ? $return['engine'] : ucfirst(static::_get($return, 'scheme'));

		$return += [
			'engine' => $engine,
		] + $defaults;

		if (isset($return['types']) && !is_array($return['types'])) {
			$return['types'] = explode(',', $return['types']);
		}

		return static::_replace($return, $replacements);
	}

/**
 * parseUrl
 *
 * Parse a url and merge with any extra get arguments defined
 *
 * @param string $string
 * @return array
 */
	public static function parseUrl($string) {
		$url = parse_url($string);
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
 * get an environment variable
 *
 * @param string $key
 * @return string
 */
	protected static function _getEnv($key) {
		$data = $_ENV + $_SERVER;

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
