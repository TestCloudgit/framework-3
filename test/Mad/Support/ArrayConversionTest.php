<?php
/**
 * @category   Mad
 * @package    Mad_Support
 * @subpackage UnitTests
 * @copyright  (c) 2007-2009 Maintainable Software, LLC
 * @license    http://opensource.org/licenses/bsd-license.php BSD 
 */

/**
 * Set environment
 */
if (!defined('MAD_ENV')) define('MAD_ENV', 'test');
if (!defined('MAD_ROOT')) {
    require_once dirname(dirname(dirname(dirname(__FILE__)))).'/config/environment.php';
}

/**
 * @todo Tests for sanitizeSql()
 * 
 * @group      support
 * @category   Mad
 * @package    Mad_Support
 * @subpackage UnitTests
 * @copyright  (c) 2007-2009 Maintainable Software, LLC
 * @license    http://opensource.org/licenses/bsd-license.php BSD
 */
class Mad_Support_ArrayConversionTest extends Mad_Test_Unit
{
    // set up new db by inserting dummy data into the db
    public function setUp()
    {        
        $this->conversion = new Mad_Support_ArrayConversion;
    }


    /*##########################################################################
    # From XML
    ##########################################################################*/

    /**
     * Note: This test have been changed from the Rails versions.  The number
     * 2592000000 was changed to 2147483647.  @see ArrayConversion->parseInt()
     */
    public function testSingleRecordFromXml()
    {
        $xml = <<< XML
<topic>
  <title>The First Topic</title>
  <author-name>David</author-name>
  <id type="integer">1</id>
  <approved type="boolean"> true </approved>
  <replies-count type="integer">0</replies-count>
  <replies-close-in type="integer">2147483647</replies-close-in>
  <written-on type="date">2003-07-16</written-on>
  <viewed-at type="datetime">2003-07-16T09:28:00+0000</viewed-at>
  <content type="yaml">---\nmessage: Have a nice day\n1: should be an integer\narray: \n  - \n    should-have-dashes: true\n    should_have_underscores: true\n</content>
  <author-email-address>david@loudthinking.com</author-email-address>
  <parent-id></parent-id>
  <ad-revenue type="decimal">1.5</ad-revenue>
  <optimum-viewing-angle type="float">135</optimum-viewing-angle>
  <resident type="symbol">yes</resident>
</topic>
XML;
        $parsed = $this->conversion->fromXml($xml);

        $expected = array(
            'title'                 => "The First Topic",
            'author_name'           => "David",
            'id'                    => 1,
            'approved'              => true,
            'replies_count'         => 0,
            'replies_close_in'      => 2147483647,
            'written_on'            => '2003-07-16',
            'viewed_at'             => '2003-07-16 02:28:00',
            'content'               => array('message' => "Have a nice day",    
                                             1         => "should be an integer", 
                                             "array"   => array(
                                                 array('should-have-dashes' => true, 
                                                       'should_have_underscores' => true))),
            'author_email_address'  => "david@loudthinking.com",
            'parent_id'             => null,
            'ad_revenue'            => 1.50,
            'optimum_viewing_angle' => 135.0,
            'resident'              => 'yes'
        );
        $this->assertEquals($expected, $parsed['topic']);
    }

    
    public function testSingleRecordFromXmlWithNilValues()
    {
        $xml = <<< XML
<topic>
  <title></title>
  <id type="integer"></id>
  <approved type="boolean"></approved>
  <written-on type="date"></written-on>
  <viewed-at type="datetime"></viewed-at>
  <content type="yaml"></content>
  <parent-id></parent-id>
</topic>
XML;
        $parsed = $this->conversion->fromXml($xml);
        $expected = array(
            'title'      => null, 
            'id'         => null,
            'approved'   => null,
            'written_on' => null,
            'viewed_at'  => null,
            'content'    => null, 
            'parent_id'  => null
        );
        $this->assertEquals($expected, $parsed['topic']);
    }

    public function testMultipleRecordsFromXml()
    {
        $xml = <<< XML
<topics type="array">
  <topic>
    <title>The First Topic</title>
    <author-name>David</author-name>
    <id type="integer">1</id>
    <approved type="boolean">false</approved>
    <replies-count type="integer">0</replies-count>
    <replies-close-in type="integer">2147483647</replies-close-in>
    <written-on type="date">2003-07-16</written-on>
    <viewed-at type="datetime">2003-07-16T09:28:00+0000</viewed-at>
    <content>Have a nice day</content>
    <author-email-address>david@loudthinking.com</author-email-address>
    <parent-id nil="true"></parent-id>
  </topic>
  <topic>
    <title>The Second Topic</title>
    <author-name>Jason</author-name>
    <id type="integer">1</id>
    <approved type="boolean">false</approved>
    <replies-count type="integer">0</replies-count>
    <replies-close-in type="integer">2147483647</replies-close-in>
    <written-on type="date">2003-07-16</written-on>
    <viewed-at type="datetime">2003-07-16T09:28:00+0000</viewed-at>
    <content>Have a nice day</content>
    <author-email-address>david@loudthinking.com</author-email-address>
    <parent-id></parent-id>
  </topic>
</topics>
XML;
        $parsed = $this->conversion->fromXml($xml);
        $expected = array(
           'title'                => "The First Topic",
           'author_name'          => "David",
           'id'                   => 1,
           'approved'             => false,
           'replies_count'        => 0,
           'replies_close_in'     => 2147483647,
           'written_on'            => '2003-07-16',
           'viewed_at'             => '2003-07-16 02:28:00',
           'content'              => "Have a nice day",
           'author_email_address' => "david@loudthinking.com",
           'parent_id'            => null
        );
        $this->assertEquals($expected, current($parsed['topics']));
    }

    public function testSingleRecordFromXmlWithAttributesOtherThanType()
    {
        $xml = <<< XML
<rsp stat="ok">
  <photos page="1" pages="1" perpage="100" total="16">
    <photo id="175756086" owner="55569174@N00" secret="0279bf37a1" server="76" title="Colored Pencil PhotoBooth Fun" ispublic="1" isfriend="0" isfamily="0"/>
  </photos>
</rsp>
XML;
        $parsed = $this->conversion->fromXml($xml);
        $expected = array(
            'id'       => "175756086",
            'owner'    => "55569174@N00",
            'secret'   => "0279bf37a1",
            'server'   => "76",
            'title'    => "Colored Pencil PhotoBooth Fun",
            'ispublic' => "1",
            'isfriend' => "0",
            'isfamily' => "0",
        );
        $this->assertEquals($expected, $parsed['rsp']['photos']['photo']);
    }

    public function testEmptyArrayFromXml()
    {
        $xml = <<< XML
<blog>
  <posts type="array"></posts>
</blog>
XML;
        $parsed = $this->conversion->fromXml($xml);
        $expected = array('blog' => array('posts' => array()));
        $this->assertEquals($expected, $parsed);
    }

    public function testEmptyArrayWithWhitespaceFromXml()
    {
        $xml = <<< XML
<blog>
  <posts type="array">
  </posts>
</blog>
XML;
        $parsed = $this->conversion->fromXml($xml);
        $expected = array('blog' => array('posts' => array()));

        $this->assertEquals($expected, $parsed);
    }

    public function testArrayWithOneEntryFromXml()
    {
        $xml = <<< XML
<blog>
  <posts type="array">
    <post>a post</post>
  </posts>
</blog>
XML;
        $parsed = $this->conversion->fromXml($xml);
        $expected = array('blog' => array('posts' => array('a post')));

        $this->assertEquals($expected, $parsed);
    }

    public function testArrayWithMultipleEntriesFromXml()
    {
        $xml = <<< XML
<blog>
  <posts type="array">
    <post>a post</post>
    <post>another post</post>
  </posts>
</blog>
XML;
        $parsed = $this->conversion->fromXml($xml);
        $expected = array('blog' => array('posts' => array('a post', 'another post')));

        $this->assertEquals($expected, $parsed);
    }

    public function testFileFromXml()
    {
        $xml = <<< XML
<blog>
  <logo type="file" name="logo.png" content_type="image/png">
    something
  </logo>
</blog>
XML;
        $parsed = $this->conversion->fromXml($xml);

        $this->assertNotNull($parsed['blog']);
        $this->assertNotNull($parsed['blog']['logo']);

        $file = $parsed['blog']['logo'];
        $this->assertEquals('logo.png',  $file->originalFilename);
        $this->assertEquals('image/png', $file->contentType);
    }

    public function testFileFromXmlWithDefaults()
    {
        $xml = <<< XML
<blog>
  <logo type="file">
    something
  </logo>
</blog>
XML;
        $parsed = $this->conversion->fromXml($xml);
        $file = $parsed['blog']['logo'];

        $this->assertEquals('untitled',                 $file->originalFilename);
        $this->assertEquals('application/octet-stream', $file->contentType);
    }

    public function testXsdLikeTypesFromXml()
    {
        $xml = <<< XML
<bacon>
  <weight type="double">0.5</weight>
  <price type="decimal">12.50</price>
  <chunky type="boolean"> 1 </chunky>
  <expires-at type="dateTime">2007-12-25T12:34:56+0000</expires-at>
  <notes type="string"></notes>
  <illustration type="base64Binary">YmFiZS5wbmc=</illustration>
</bacon>
XML;
        $parsed = $this->conversion->fromXml($xml);
        $expected = array(
          'weight'       => 0.5,
          'chunky'       => true,
          'price'        => 12.50,
          'expires_at'   => '2007-12-25 04:34:56',
          'notes'        => "",
          'illustration' => "babe.png"
        );
        $this->assertEquals($expected, $parsed['bacon']);
    }

    public function testTypeTricklesThroughWhenUnknown()
    {
        $xml = <<< XML
<product>
  <weight type="double">0.5</weight>
  <image type="ProductImage"><filename>image.gif</filename></image>
</product>
XML;
        $parsed = $this->conversion->fromXml($xml);
        $expected = array(
            'weight' => 0.5, 
            'image'  => array('type' => 'ProductImage', 'filename' => 'image.gif')
        );
        $this->assertEquals($expected, $parsed['product']);
    }
    

    /*##########################################################################
    # Hash To XML
    ##########################################################################*/

    public function testOneLevel() 
    {
        $hash = array('name' => 'David', 'street' => 'Paulina');
        $xml = $this->conversion->hashToXml($hash, $this->xmlOptions());

        $this->assertEquals("<person>", substr($xml, 0, 8));
        $this->assertContains('<street>Paulina</street>', $xml);
        $this->assertContains('<name>David</name>',       $xml);
    }

    public function testOneLevelDasherizeFalse()
    {
        $hash = array('name' => 'David', 'street_name' => 'Paulina');
        $xml = $this->conversion->hashToXml($hash, $this->xmlOptions(array('dasherize' => false)));

        $this->assertEquals("<person>", substr($xml, 0, 8));
        $this->assertContains('<street_name>Paulina</street_name>', $xml);
        $this->assertContains('<name>David</name>', $xml);
    }

    public function testOneLevelDasherizeTrue()
    {
        $hash = array('name' => 'David', 'street_name' => 'Paulina');
        $xml = $this->conversion->hashToXml($hash, $this->xmlOptions(array('dasherize' => true)));

        $this->assertEquals("<person>", substr($xml, 0, 8));
        $this->assertContains('<street-name>Paulina</street-name>', $xml);
        $this->assertContains('<name>David</name>', $xml);
    }

    /**
     * Note: This test have been changed from the Rails version.  The number
     * 820497600000 was changed to 2147483647 to run on 32-bit PHP.  
     * @see ArrayConversion->parseInt() for an explanation of the overflow issue.
     */
    public function testOneLevelWithTypes()
    {
        $hash = array('name'          => 'David', 
                      'street_name'   => 'Paulina', 
                      'age'           => 26, 
                      'age_in_millis' => 2147483647, 
                      'moved_on'      => '2005-11-15', 
                      'resident'      => true);
        $xml = $this->conversion->hashToXml($hash, $this->xmlOptions());

        $this->assertEquals("<person>", substr($xml, 0, 8));
        $this->assertContains('<street-name>Paulina</street-name>',       $xml);
        $this->assertContains('<name>David</name>',                       $xml);
        $this->assertContains('<age type="integer">26</age>',             $xml);
        $this->assertContains('<moved-on>2005-11-15</moved-on>',          $xml);
        $this->assertContains('<resident type="boolean">true</resident>', $xml);
    }

    public function testOneLevelWithNils()
    {
        $hash = array('name' => 'David', 'street' => 'Paulina', 'age' => null);
        $xml = $this->conversion->hashToXml($hash, $this->xmlOptions());

        $this->assertEquals("<person>", substr($xml, 0, 8));
        $this->assertContains('<street>Paulina</street>', $xml);
        $this->assertContains('<name>David</name>',       $xml);
        $this->assertContains('<age nil="true"></age>',   $xml);
    }

    public function testOneLevelWithSkippingTypes()
    {
        $hash = array('name' => 'David', 'street' => 'Paulina', 'age' => null, 'resident' => true);
        $xml = $this->conversion->hashToXml($hash, $this->xmlOptions(array('skipTypes' => true)));

        $this->assertEquals("<person>", substr($xml, 0, 8));
        $this->assertContains('<street>Paulina</street>',  $xml);
        $this->assertContains('<name>David</name>',        $xml);
        $this->assertContains('<age nil="true"></age>',    $xml);
        $this->assertContains('<resident>true</resident>', $xml);
    }

    public function testTwoLevels()
    {
        $hash = array('name' => 'David', 'address' => array('street' => 'Paulina'));
        $xml = $this->conversion->hashToXml($hash, $this->xmlOptions());
        
        $this->assertEquals("<person>", substr($xml, 0, 8));
        $this->assertContains('<address><street>Paulina</street></address>', $xml);
        $this->assertContains('<name>David</name>', $xml);
    }

    public function testTwoLevelsWithSecondLevelOverridingToXml()
    {
        $hash = array('name' => 'David', 'address' => array('street' => 'Paulina'), 'child' => new IWriteMyOwnXML);
        $xml = $this->conversion->hashToXml($hash, $this->xmlOptions());
        
        $this->assertEquals("<person>", substr($xml, 0, 8));
        $this->assertContains('<address><street>Paulina</street></address>', $xml);
        $this->assertContains('<level_one><second_level>content</second_level></level_one>', $xml);
    }

    public function testTwoLevelsWithArray()
    {
        $hash = array('name' => 'David', 'addresses' => array(array('street' => 'Paulina'), 
                                                              array('street' => 'Evergreen')));
        $xml = $this->conversion->hashToXml($hash, $this->xmlOptions());

        $this->assertEquals("<person>", substr($xml, 0, 8));
        
        $this->assertContains('<addresses type="array"><address>', $xml);
        $this->assertContains('<address><street>Paulina</street></address>', $xml);
        $this->assertContains('<address><street>Evergreen</street></address>', $xml);
        $this->assertContains('<name>David</name>', $xml);
    }

    public function testThreeLevelsWithArray()
    {
        $hash = array('name' => 'David', 'addresses' => array(array('streets' => array(array('name' => "Paulina"), 
                                                                                       array('name' => "Paulina")))));
        $xml = $this->conversion->hashToXml($hash, $this->xmlOptions());

        $this->assertContains('<addresses type="array"><address><streets type="array"><street><name>', $xml);
    }



    /*##########################################################################
    # Array To XML
    ##########################################################################*/
 
    /**
     * Note: This test have been changed from the Rails version.  The number
     * 820497600000 was changed to 2147483647 to run on 32-bit PHP.  
     * @see ArrayConversion->parseInt() for an explanation of the overflow issue.
     */
    public function testToXml()
    {
        $array = array(
            array('name' => "David", 'age' => 26, 'age_in_millis' => 2147483647), 
            array('name' => "Jason", 'age' => 31, 'age_in_millis' => 1.1)
        );
        $xml = $this->conversion->arrayToXml($array, array('skipInstruct' => true, 'indent' => 0));

        $this->assertEquals('<records type="array"><record>', substr($xml, 0, 30));
        $this->assertContains('<age type="integer">26</age>', $xml);
        $this->assertContains('<name>David</name>',           $xml);
        $this->assertContains('<age type="integer">31</age>', $xml);
        $this->assertContains('<name>Jason</name>',           $xml);
        $this->assertContains('<age-in-millis type="integer">2147483647</age-in-millis>', $xml);
        $this->assertContains('<age-in-millis type="float">1.1</age-in-millis>', $xml);
    }

    /**
     * Note: This test have been changed from the Rails version.  The number
     * 820497600000 was changed to 2147483647 to run on 32-bit PHP.  
     * @see ArrayConversion->parseInt() for an explanation of the overflow issue.
     */
    public function testToXmlWithDedicatedName()
    {
        $array = array(
            array('name' => "David", 'age' => 26, 'age_in_millis' => 2147483647)
        );
        $xml = $this->conversion->arrayToXml($array, array('skipInstruct' => true, 'indent' => 0, 
                                                           'root'         => 'people'));

        $this->assertEquals('<people type="array"><person>', substr($xml, 0, 29));
    }

    public function testToXmlWithOptions()
    {
        $array = array(
            array('name' => "David", 'street_address' => 'Paulina'), 
            array('name' => "Jason", 'street_address' => 'Evergreen')
        );
        $xml = $this->conversion->arrayToXml($array, array('skipInstruct' => true, 'indent' => 0, 
                                                           'skipTypes'    => true));

        $this->assertEquals('<records><record>', substr($xml, 0, 17));
        $this->assertContains('<street-address>Paulina</street-address>',   $xml);
        $this->assertContains('<name>David</name>',                         $xml);
        $this->assertContains('<street-address>Evergreen</street-address>', $xml);
        $this->assertContains('<name>Jason</name>',                         $xml);
    }

    public function testToXmlWithDasherizeFalse()
    {
        $array = array(
            array('name' => "David", 'street_address' => 'Paulina'),
            array('name' => "Jason", 'street_address' => 'Evergreen')
        );
        $xml = $this->conversion->arrayToXml($array, array('skipInstruct' => true, 'indent'    => 0, 
                                                           'skipTypes'    => true, 'dasherize' => false));

        $this->assertEquals('<records><record>', substr($xml, 0, 17));
        $this->assertContains('<street_address>Paulina</street_address>',   $xml);
        $this->assertContains('<street_address>Evergreen</street_address>', $xml);
    }

    public function testToXmlWithDasherizeTrue()
    {
        $array = array(
            array('name' => "David", 'street_address' => 'Paulina'),
            array('name' => "Jason", 'street_address' => 'Evergreen')
        );
        $xml = $this->conversion->arrayToXml($array, array('skipInstruct' => true, 'indent'    => 0, 
                                                           'skipTypes'    => true, 'dasherize' => true));

        $this->assertEquals('<records><record>', substr($xml, 0, 17));
        $this->assertContains('<street-address>Paulina</street-address>',   $xml);
        $this->assertContains('<street-address>Evergreen</street-address>', $xml);
    }

    /**
     * Note: This test have been changed from the Rails version.  The number
     * 820497600000 was changed to 2147483647 to run on 32-bit PHP.  
     * @see ArrayConversion->parseInt() for an explanation of the overflow issue.
     */
    public function testToWithInstruct()
    {
        $array = array(
            array('name' => "David", 'age' => 26, 'age_in_millis' => 2147483647), 
            array('name' => "Jason", 'age' => 31, 'age_in_millis' => 1.0)
        );
        $xml = $this->conversion->arrayToXml($array, array('skipInstruct' => false, 'indent' => 0));

        $this->assertEquals('<?xml', substr($xml, 0, 5));
    }

    public function testToXmlWithEmpty()
    {
        $array = array();
        $xml = $this->conversion->arrayToXml($array, array('skipInstruct' => true, 'indent' => 0));

        $this->assertEquals('<records type="array"></records>', $xml);
    }


    /*##########################################################################
    ##########################################################################*/   
    
    
    protected function xmlOptions($options = array()) 
    {
        return array_merge(array(
            'root'         => 'person', 
            'skipInstruct' => true, 
            'indent'       => 0
        ), $options);
    }
}


/**
 * Test class for custom toXml method on objects
 */
class IWriteMyOwnXML
{
    public function toXml($options = array()) 
    {
        if (!isset($options['indent'])) { $options['indent'] = 2; }

        if (empty($options['builder'])) {
            $options['builder'] = new Mad_Support_Builder(
                array('indent' => $options['indent']));
        }
        
        $tag = $options['builder']->startTag('level_one');
            $tag->tag('second_level', 'content');
        $tag->end();

        return (string)$options['builder'];
    }
}
