<?php

use TorneLIB\TorneLIB_IO;
use TorneLIB\TorneLIB_PDU_Encoder;
use PHPUnit\Framework\TestCase;

if ( file_exists( __DIR__ . '/../vendor/autoload.php' ) ) {
	require_once( __DIR__ . '/../vendor/autoload.php' );
}

class ioTest extends TestCase {

	/** @var TorneLIB_IO $IO */
	private $IO;
	/** @var TorneLIB_PDU_Encoder $PDU */
	private $PDU;

	private $BIT_STRING = "This is a swedish test containing räksmörgåsar! (With smörgårsbord)";

	private $arr = array(
		'a' => 'b',
		'b' => array(
			'c' => 'd'
		)
	);

	private $obj;

	function setUp() {
		$this->IO = new TorneLIB_IO();
		//$this->PDU = new \TorneLIB\TorneLIB_PDU_Encoder();
		$this->obj               = new stdClass();
		$this->obj->a            = new stdClass();
		$this->obj->a->nextLevel = array(
			'arrayLevel' => "part 1",
			'nextLevel'  => array(
				'recursiveLevel' => 'yes'
			)
		);
	}

	/**
	 * @test
	 */
	function objectToArray() {
		$convert = $this->IO->objectsIntoArray( $this->obj );
		static::assertTrue( is_array( $convert['a'] ) && is_array( $convert['a']['nextLevel'] ) && is_array( $convert['a']['nextLevel']['nextLevel'] ) );
	}

	/**
	 * @test
	 */
	function arrayToObject() {
		$convert = $this->IO->arrayObjectToStdClass( $this->arr );
		static::assertTrue( isset( $convert->a ) && is_object( $convert->b ) && isset( $convert->b->c ) && $convert->b->c == "d" );
	}

	/**
	 * @test
	 * @throws Exception
	 */
	function renderJsonApiLike() {
		static::assertTrue( strlen( $this->IO->renderJson( $this->obj ) ) == 170 );
	}

	/**
	 * @test
	 * @throws Exception
	 */
	function renderSerializedApiLike() {
		static::assertTrue( strlen( $this->IO->renderPhpSerialize( $this->obj ) ) == 153 );
	}

	/**
	 * @test
	 */
	function renderYamlApiLike() {
		$yamlString = null;
		try {
			$yamlString = $this->IO->renderYaml( $this->obj );
		} catch ( \Exception $yamlException ) {
			static::markTestSkipped( $yamlException->getMessage() );
		}
		static::assertTrue( strlen( $yamlString ) == 90 );
	}

	/**
	 * @test
	 * @throws Exception
	 */
	function renderXmlApiLike() {
		if ( $this->IO->getHasXmlSerializer() ) {
			static::assertTrue( strlen( $this->IO->renderXml( $this->obj ) ) == 248 );
		} else {
			static::markTestSkipped( "Primary class for this test (XML_Serializer) is missing on this system" );
		}
	}

	/**
	 * @test
	 * @throws Exception
	 */
	function tenderSimpleXmlApiLike() {
		$this->IO->setXmlSimple( true );
		static::assertTrue( strlen( $this->IO->renderXml( $this->obj ) ) == 156 );
	}

	/**
	 * @test
	 * @throws Exception
	 */
	function renderGzCompressedJsonApiLike() {
		static::assertTrue( strlen( $this->IO->renderJson( $this->obj, false, \TorneLIB\TORNELIB_CRYPTO_TYPES::TYPE_GZ ) ) == 123 );
	}

	/**
	 * @test
	 * @throws Exception
	 */
	function renderBz2CompressedJsonApiLike() {
		if ( function_exists( 'bzcompress' ) ) {
			static::assertTrue( strlen( $this->IO->renderJson( $this->obj, false, \TorneLIB\TORNELIB_CRYPTO_TYPES::TYPE_BZ2 ) ) == 148 );
		} else {
			$this->markTestSkipped( 'bzcompress is missing on this server, could not complete test' );
		}
	}

	/**
	 * @test
	 * @throws Exception
	 */
	function renderGzSerializedApiLike() {
		$this->IO->setCompressionLevel( 9 );
		static::assertTrue( strlen( $this->IO->renderPhpSerialize( $this->obj, false, \TorneLIB\TORNELIB_CRYPTO_TYPES::TYPE_GZ ) ) == 156 );
	}

	/**
	 * @test
	 */
	function getFromJson() {
		static::assertTrue( isset( $this->IO->getFromJson( json_encode( $this->arr ) )->a ) );
	}

	/**
	 * @test
	 */
	function getFromJsonBadType() {
		$thitIsNotJsonString = $this->IO->getFromJson( $this->arr );
		static::assertTrue( is_null( $thitIsNotJsonString ) );
	}

	/**
	 * @test
	 * @throws Exception
	 */
	function getReverseJsonFromRender() {
		static::assertTrue( strlen( $this->IO->renderJson( json_encode( $this->arr ) ) ) > 50 );
	}

	/**
	 * @test
	 * @throws Exception
	 */
	function getFromSimpleXml() {
		$this->IO->setXmlSimple( true );
		$xmlObjectString = $this->IO->renderXml( $this->obj );
		/** @var SimpleXMLElement $xmlElement */
		$xmlElements = $this->IO->getFromXml( $xmlObjectString, true );
		static::assertTrue( isset( $xmlElements->a ) );
	}

	/**
	 * @test
	 * @throws Exception
	 */
	function getFromXmlSerializer() {
		$this->IO->setXmlSimple( false );
		$this->IO->setXmlUnSerializer( true );
		$xmlObjectString = $this->IO->renderXml( $this->obj );
		$xmlElements     = $this->IO->getFromXml( $xmlObjectString, true );
		static::assertTrue( isset( $xmlElements->a ) );
	}

	/**
	 * @test
	 * @throws Exception
	 */
	function getFromYaml() {
		$yaml       = $this->IO->renderYaml( $this->arr );
		$yamlArray  = $this->IO->getFromYaml( $yaml );
		$yamlObject = $this->IO->getFromYaml( $yaml, false );
		static::assertTrue( is_array( $yamlArray ) && is_object( $yamlObject ) && isset( $yamlArray['a'] ) && isset( $yamlObject->a ) );
	}

	/**
	 * @test
	 * @throws Exception
	 */
	function getFromBadYaml() {

		try {
			$this->IO->getFromYaml( null );
		} catch ( \Exception $e ) {
			static::assertStringEndsWith( 'end of stream reached without finding document 0', $e->getMessage() );
		}
	}

	/**
	 * @test
	 * @throws Exception
	 */
	function getFromRegularSerial() {
		static::assertTrue( isset( $this->IO->getFromSerializerInternal( $this->IO->renderPhpSerialize( $this->arr ) )['a'] ) );
	}

	/**
	 * @test
	 */
	function getFromBadSerial() {
		static::assertTrue( empty( $this->IO->getFromSerializerInternal( 'fail_this' ) ) );
	}

}
