<?php
namespace WP_Rocket\Tests\Unit\Workingtests;

use Mockery;
use WP_Rocket\Engine\Optimization\GoogleFonts\CombineV2;
use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;
use WP_Rocket\Logger\Logger;

/**
 * @covers
 * @uses
 *
 * @group
 */
class CombineV2Test extends TestCase {
	public function setUp() {
		Monkey\setUp();

		Functions\when('wp_parse_url')
			->alias('parse_url');
		Functions\when('wp_parse_args')
			->alias(function ($args, $defaults = array()) {
					return parse_str( $args, $parsed_args );
			});
		Functions\when('esc_url')->returnArg();

		parent::setUp();
	}

	public function tearDown() {
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * @dataProvider provide
	 */
	public function testOptimize($given, $expected): void
	{
		$expected = $this->format_the_html($expected);
		$combiner = new CombineV2();

		$actual = $this->format_the_html($combiner->optimize($given));

		$this->assertEquals($expected, $actual);
	}

	private function format_the_html( $html ) {
		$html = trim( $html );
		$html = preg_replace( '/(\>)\s*(\<)/m', '$1$2', $html );
		$html = preg_replace( '/(\>)\s*/m', '$1$2', $html );
		$html = preg_replace( '/\s*(\<)/m', '$1$2', $html );

		return str_replace( '>', ">\n", $html );
	}

	public function provide(): array
	{
		return [

			'shouldReturnGivenHTMLWhenNoRelevantTags' => [
				'given' => <<<GIVEN
<!doctype html>
<html>
	<head>
		<title>Sample Page</title>
		<link rel="stylesheet" id="dt-web-fonts-css" href="https://fonts.googleapis.com/css?family=some+previous+api+spec" type="text/css" media="all" />
	</head>
	<body>
	</body>
</html>
GIVEN
				,
				'expected' => <<<EXPECTED
<!doctype html>
<html>
	<head>
		<title>Sample Page</title>
		<link rel="stylesheet" id="dt-web-fonts-css" href="https://fonts.googleapis.com/css?family=some+previous+api+spec" type="text/css" media="all" />
	</head>
	<body>
	</body>
</html>
EXPECTED
			],

			'shouldReturnTagWithFontDisplayWhenSingleTagGiven' => [
				'given' => <<<GIVEN
<!doctype html>
<html>
	<head>
		<title>Sample Page</title>
		<link rel="stylesheet" id="dt-web-fonts-css" href="https://fonts.googleapis.com/css2?family=Crimson+Pro:wght@450" type="text/css" media="all" />
	</head>
	<body>
	</body>
</html>
GIVEN
				,
				'expected' => <<<EXPECTED
<!doctype html>
<html>
	<head>
		<title>Sample Page</title>
		<link rel="stylesheet" id="dt-web-fonts-css" href="https://fonts.googleapis.com/css2?family=Crimson+Pro:wght@450&display=swap" type="text/css" media="all" />
	</head>
	<body>
	</body>
</html>
EXPECTED
			],

			'shouldCombineMultipleTags' => [
				'given' => <<<GIVEN
<!doctype html>
<html>
	<head>
		<title>Sample Page</title>
		<link rel="stylesheet" id="dt-web-fonts-css" href="https://fonts.googleapis.com/css2?family=Crimson+Pro:wght@450" type="text/css" media="all" />
		<link rel="stylesheet" id="dt-more-fonts-css" href="https://fonts.googleapis.com/css2?family=Comfortaa&amp;text=Hello" type="text/css" media="all" />
	</head>
	<body>
	</body>
</html>
GIVEN
				,
				'expected' => <<<EXPECTED
<!doctype html>
<html>
	<head>
		<title>Sample Page</title><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Crimson%2BPro%3Awght%40450&?family=Comfortaa%26amp%3Btext%3DHello&display=swap" />
	</head>
	<body>
	</body>
</html>
EXPECTED
			],
		];
	}
}
