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
			if (strpos($val, $prefix) !== 0) {
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
 * @param string $prefix
 * @param mixed $duration
 * @return array
 */
	public static function parseCache($prefix = 'my_app_', $duration = null) {
		$configs = static::allByPrefix('CACHE_URL');
		if (!$configs) {
			return false;
		}

		if ($duration === null) {
			$duration = '+999 days';

			if (Configure::read('debug') > 0) {
				$duration = '+10 seconds';
			}
		}

		$replacements = [
			'PREFIX' => isset($configs['default']['prefix']) ? $configs['default']['prefix'] : $prefix,
			'/CACHE/' => CACHE,
		];

		foreach($configs as $connection => $url) {
			$config = static::parseUrl($url);
			if (!$config) {
				continue;
			}


			$name = isset($config['name']) ? $config['name'] : strtolower(trim($connection, '_'));
			$engine = isset($config['engine']) ? $config['engine'] : ucfirst(Hash::get($config, 'scheme'));

			$config += [
				'engine' => $engine,
				'serialize' => ($engine === 'File'),
				'duration' => $duration,
				'login' => Hash::get($config, 'user'),
				'password' => Hash::get($config, 'pass'),
				'server' => Hash::get($config, 'host'),
				'servers' => Hash::get($config, 'host')
			];

			foreach($config as &$val) {
				$val = str_replace(array_keys($replacements), array_values($replacements), $val);
			}


			$return[$name] = $config;
		}

		return $return;
	}

/**
 * parseDb
 *
 * @return array
 */
	public static function parseDb() {
		$configs = static::allByPrefix('DATABASE_URL');
		if (!$configs) {
			return false;
		}

		foreach ($configs as $connection => $url) {
			$parsed = static::parseUrl($url);
			if (!$parsed) {
				continue;
			}

			$connection = strtolower($connection);
			$return[$connection] = $parsed + [
				'datasource' => 'Database/' . ucfirst(strtolower($parsed['scheme'])),
				'persistent' => Hash::get($parsed, 'persistent'),
				'host'       => Hash::get($parsed, 'host'),
				'login'      => Hash::get($parsed, 'user'),
				'password'   => Hash::get($parsed, 'pass'),
				'database'   => substr($parsed['path'], 1),
				'persistent' => Hash::get($parsed, 'persistent'),
				'encoding'   => Hash::get($parsed, 'encoding') ?: 'utf8',
			];
		}

		return $return;
	}

/**
 * parseLogs
 *
 * @return array
 */
	public static function parseLogs() {
		$configs = static::allByPrefix('LOG_URL', 'debug');
		if (!$configs) {
			return false;
		}

		$replacements = [
			'/LOGS/' => LOGS
		];
		foreach($configs as $connection => $url) {
			$config = static::parseUrl($url);
			if (!$config) {
				continue;
			}


			$name = isset($config['name']) ? $config['name'] : strtolower(trim($connection, '_'));
			$engine = isset($config['engine']) ? $config['engine'] : ucfirst(Hash::get($config, 'scheme'));

			$config += [
				'engine' => $engine,
				'file' => $name
			];

			if (isset($config['types']) && !is_array($config['types'])) {
				$config['types'] = explode(',', $config['types']);
			}

			foreach($config as &$val) {
				$val = str_replace(array_keys($replacements), array_values($replacements), $val);
			}

			$return[$name] = $config;
		}

		return $return;
	}
}
