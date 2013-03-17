<?php

namespace mf2;

use DOMDocument,
    DOMElement,
    DOMXPath,
    DOMNode,
    DOMNodeList,
    DateTime,
    Exception;

class Parser {
    /** @var string The baseurl (if any) to use for this parse */
    public $baseurl;

    /** @var DOMXPath object which can be used to query over any fragment*/
    protected $xpath;
    
    /** @var bool Whether or not to output datetimes as strings */
    public $stringDateTimes = false;
    
    /** @var SplObjectStorage */
    private $parsed;
    
    /** @var DOMDocument */
    private $doc;

    /**
     * Constructor
     * 
     * @param DOMDocument|string $input The data to parse. A string of DOM or a DOMDocument
     */
    public function __construct($input, $baseurl = null) {
        // For the moment: assume string = string of HTML
        if (is_string($input)) {
            if (strtolower(mb_detect_encoding($input)) == 'utf-8') {
                $input = mb_convert_encoding($input, 'HTML-ENTITIES', "UTF-8");
            }

            $doc = new DOMDocument();
            @$doc->loadHTML($input);
        }
        elseif (is_a($input, 'DOMDocument')) {
            $doc = $input;
        }
        
        $this->doc = $doc;

        $this->parsed = new \SplObjectStorage();

        // TODO: Check for <base> if $baseURL not supplied
        $this->baseurl = $baseurl;
    }

    // !Utility Functions
    
    /**
     * Collapse Whitespace
     * 
     * Collapses any sequences of whitespace within a string into a single space
     * character.
     * 
     * @param string $str
     * @return string
     */
    public static function collapseWhitespace($str) {
        return preg_replace('/[\s|\n]+/', ' ', $str);
    }

    /**
     * Microformat Name From Class string
     * 
     * Given the value of @class, get the relevant mf classnames (e.g. h-card, 
     * p-name).
     * 
     * @param string $class A space delimited list of classnames
     * @param string $prefix The prefix to look for
     * @return string|array The prefixed name of the first microfomats class found or false
     */
    public static function mfNamesFromClass($class, $prefix = 'h-') {
        $classes = explode(' ', $class);
        $matches = array();
        
        foreach ($classes as $classname) {
            if (stristr(' ' . $classname, ' ' . $prefix) !== false) {
                $matches[] = ($prefix === 'h-') ? $classname : substr($classname, strlen($prefix));
            }
        }

        return $matches;
    }
    
    /**
     * Get Nested µf Property Name From Class
     * 
     * Returns all the p-, u-, dt- or e- prefixed classnames it finds in a 
     * space-separated string.
     * 
     * @param string $class
     * @return string|null
     */
    public static function nestedMfPropertyNamesFromClass($class) {
        $prefixes = array('p-', 'u-', 'dt-', 'e-');
        
        foreach (explode(' ', $class) as $classname) {
            foreach ($prefixes as $prefix) {
                if (stristr($classname, $prefix))
                    return self::mfNamesFromClass($classname, $prefix);
            }
        }
        
        return null;
    }

    /**
     * Wraps mfNamesFromClass to handle an element as input (common)
     * 
     * @param DOMElement $e The element to get the classname for
     * @param string $prefix The prefix to look for
     * @return mixed See return value of mf2\Parser::mfNameFromClass()
     */
    static function mfNamesFromElement(\DOMElement $e, $prefix = 'h-') {
        $class = $e->getAttribute('class');
        return Parser::mfNamesFromClass($class, $prefix);
    }
    
    /**
     * Wraps nestedMfPropertyNamesFromClass to handle an element as input
     */
    static function nestedMfPropertyNamesFromElement(\DOMElement $e) {
        $class = $e->getAttribute('class');
        return self::nestedMfPropertyNamesFromClass($class);
    }
    
    private function elementPrefixParsed(\DOMElement $e, $prefix) {
        if (!$this->parsed->contains($e))
            $this->parsed->attach($e, array());
        
        $prefixes = $this->parsed[$e];
        $prefixes[] = $prefix;
        $this->parsed[$e] = $prefixes;
    }
    
    private function isElementParsed(\DOMElement $e, $prefix) {
        if (!$this->parsed->contains($e))
            return false;
        
        return true;
    }
    
    // !Parsing Functions
    
    /**
     * Parse value-class/value-title on an element, joining with $separator if 
     * there are multiple.
     * 
     * @param \DOMElement $e
     * @param string $separator = '' if multiple value-title elements, join with this string
     * @return string|null the parsed value or null if value-class or -title aren’t in use
     */
    public function parseValueClassTitle(\DOMElement $e, $separator = '') {
        $valueClassElements = $this->xpath->query('.//*[contains(concat(" ", @class, " "), " value ")]', $e);
        
        if ($valueClassElements->length !== 0) {
            // Process value-class stuff
            $val = '';
            foreach ($valueClassElements as $el) {
                $val .= $el->textContent . $separator;
            }
            
            return trim($val);
        }
        
        $valueTitleElements = $this->xpath->query('.//*[contains(concat(" ", @class, " "), " value-title ")]', $e);
        
        if ($valueTitleElements->length !== 0) {
            // Process value-title stuff
            $val = '';
            foreach ($valueTitleElements as $el) {
                $val .= $el->getAttribute('title') . $separator;
            }
            
            return trim($val);
        }
        
        // No value-title or -class in this element
        return null;
    }
    
    /**
     * Given an element with class="p-*", get it’s value
     * 
     * @param DOMElement $p The element to parse
     * @return string The plaintext value of $p, dependant on type
     * @todo Make this adhere to value-class
     */
    public function parseP(\DOMElement $p) {
        $classTitle = $this->parseValueClassTitle($p, ' ');
        
        if ($classTitle !== null)
            return $classTitle;
        
        if (in_array($p->tagName, array('br', 'hr')))
            return '';
        elseif ($p->tagName == 'img' and $p->getAttribute('alt') !== '') {
            $pValue = $p->getAttribute('alt');
        } elseif ($p->tagName == 'area' and $p->getAttribute('alt') !== '') {
            $pValue = $p->getAttribute('alt');
        } elseif ($p->tagName == 'abbr' and $p->getAttribute('title') !== '') {
            $pValue = $p->getAttribute('title');
        } elseif ($p->tagName == 'data' and $p->getAttribute('value') !== '') {
            $pValue = $p->getAttribute('value');
        } else {
            // Use innertext
            $pValue = trim($p->textContent);
        }
        
        return self::collapseWhitespace($pValue);
    }

    /**
     * Given an element with class="u-*", get the value of the URL
     * 
     * @param DOMElement $u The element to parse
     * @return string The plaintext value of $u, dependant on type
     * @todo make this adhere to value-class
     */
    public function parseU(\DOMElement $u) {
        $classTitle = $this->parseValueClassTitle($u);
        
        if ($classTitle !== null)
            return $classTitle;
        
        if (($u->tagName == 'a' or $u->tagName == 'area') and $u->getAttribute('href') !== null) {
            $uValue = $u->getAttribute('href');
        } elseif ($u->tagName == 'img' and $u->getAttribute('src') !== null) {
            $uValue = $u->getAttribute('src');
        } elseif ($u->tagName == 'object' and $u->getAttribute('data') !== null) {
            $uValue = $u->getAttribute('data');
        } elseif ($u->tagName == 'abbr' and $u->getAttribute('title') !== null) {
            $uValue = $u->getAttribute('title');
        } elseif ($u->tagName == 'data' and $u->getAttribute('value') !== null) {
            $uValue = $u->getAttribute('value');
        } else {
            // TODO: Check for element contents == a valid URL
            $uValue = false;
        }

        if ($uValue !== false) {
            $host = parse_url($uValue, PHP_URL_HOST);
            $scheme = parse_url($uValue, PHP_URL_SCHEME);
            if (empty($scheme) and empty($host) and !empty($this->baseurl)) {
                $uValue = $this->baseurl . $uValue;
            }
        }

        return $uValue;
    }

    /**
     * Given an element with class="dt-*", get the value of the datetime as a php date object
     * 
     * @param DOMElement $dt The element to parse
     * @return string The datetime string found
     */
    public function parseDT(\DOMElement $dt) {
        // Check for value-class pattern
        $valueClassChildren = $this->xpath->query('.//*[contains(concat(" ", @class, " "), " value ")]', $dt);
        $dtValue = false;

        if ($valueClassChildren->length > 0) {
            // They’re using value-class (awkward bugger :)
            $dateParts = array();

            foreach ($valueClassChildren as $e) {
                if ($e->tagName == 'img' or $e->tagName == 'area') {
                    // Use @alt
                    $alt = $e->getAttribute('alt');
                    if (!empty($alt))
                        $dateParts[] = $alt;
                }
                elseif ($e->tagName == 'data') {
                    // Use @value, otherwise innertext
                    $value = $e->hasAttribute('value') ? $e->getAttribute('value') : trim($e->nodeValue);
                    if (!empty($value))
                        $dateParts[] = $value;
                }
                elseif ($e->tagName == 'abbr') {
                    // Use @title, otherwise innertext
                    $title = $e->hasAttribute('title') ? $e->getAttribute('title') : trim($e->nodeValue);
                    if (!empty($title))
                        $dateParts[] = $title;
                }
                elseif ($e->tagName == 'del' or $e->tagName == 'ins' or $e->tagName == 'time') {
                    // Use @datetime if available, otherwise innertext
                    $dtAttr = ($e->hasAttribute('datetime')) ? $e->getAttribute('datetime') : trim($e->nodeValue);
                    if (!empty($dtAttr))
                        $dateParts[] = $dtAttr;
                }
                else {
                    // Use innertext
                    if (!empty($e->nodeValue))
                        $dateParts[] = trim($e->nodeValue);
                }
            }

            // Look through dateParts
            $datePart = '';
            $timePart = '';
            foreach ($dateParts as $part) {
                // Is this part a full ISO8601 datetime?
                if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(Z?[+|-]\d{2}:?\d{2})?$/', $part)) {
                    // Break completely, we’ve got our value
                    $dtValue = $part;
                    break;
                } else {
                    // Is the current part a valid time(+TZ?) AND no other time reprentation has been found?
                    if ((preg_match('/\d{2}:\d{2}(Z?[+|-]\d{2}:?\d{2})?/', $part) or preg_match('/\d{1,2}[a|p]m/', $part)) and empty($timePart)) {
                        $timePart = $part;
                    } elseif (preg_match('/\d{4}-\d{2}-\d{2}/', $part) and empty($datePart)) {
                        // Is the current part a valid date AND no other date reprentation has been found?
                        $datePart = $part;
                    }
                    
                    $dtValue = rtrim($datePart, 'T') . 'T' . trim($timePart, 'T');
                }
            }
        } else {
            // Not using value-class (phew).
            if ($dt->tagName == 'img' or $dt->tagName == 'area') {
                // Use @alt
                // Is it an entire dt?
                $alt = $dt->getAttribute('alt');
                if (!empty($alt))
                    $dtValue = $alt;
            } elseif ($dt->tagName == 'data') {
                // Use @value, otherwise innertext
                // Is it an entire dt?
                $value = $dt->getAttribute('value');
                if (!empty($value))
                    $dtValue = $value;
                else
                    $dtValue = $dt->nodeValue;
            } elseif ($dt->tagName == 'abbr') {
                // Use @title, otherwise innertext
                // Is it an entire dt?
                $title = $dt->getAttribute('title');
                if (!empty($title))
                    $dtValue = $title;
                else
                    $dtValue = $dt->nodeValue;
            } elseif ($dt->tagName == 'del' or $dt->tagName == 'ins' or $dt->tagName == 'time') {
                // Use @datetime if available, otherwise innertext
                // Is it an entire dt?
                $dtAttr = $dt->getAttribute('datetime');
                if (!empty($dtAttr))
                    $dtValue = $dtAttr;
                else
                    $dtValue = $dt->nodeValue;
            } else {
                // Use innertext
                $dtValue = $dt->nodeValue;
            }
        }

        return $dtValue;
    }

    /**
     * 	Given the root element of some embedded markup, return a string representing that markup
     *
     * 	@param DOMElement $e The element to parse
     * 	@return string $e’s innerHTML
     */
    public function parseE(\DOMElement $e) {
        $classTitle = $this->parseValueClassTitle($e);
        
        if ($classTitle !== null)
            return $classTitle;
        
        $return = '';
        foreach ($e->childNodes as $node) {
            $return .= $node->C14N();
        }
        return $return;
    }

    /**
     * Recursively parse microformats
     * 
     * @param DOMElement $e The element to parse
     * @return array A representation of the values contained within microformat $e
     */
    public function parseH(\DOMElement $e) {
        // If it’s already been parsed (e.g. is a child mf), skip
        if ($this->parsed->contains($e))
            return null;

        // Get current µf name
        $mfTypes = self::mfNamesFromElement($e, 'h-');

        // Initalise var to store the representation in
        $return = array();
        $children = array();

        // Handle nested microformats (h-*)
        foreach ($this->xpath->query('.//*[contains(concat(" ", @class)," h-")]', $e) as $subMF) {
            // Parse
            $result = $this->parseH($subMF);
            
            $result['value'] = $this->parseP($subMF);

            // Does this µf have any property names other than h-*?
            $properties = self::nestedMfPropertyNamesFromElement($subMF);
            
            if (!empty($properties)) {
                // Yes! It’s a nested property µf
                foreach ($properties as $property) {
                    $return[$property][] = $result;
                }
            } else {
                // No, it’s a child µf
                $children[] = $result;
            }
            
            // Make sure this sub-mf won’t get parsed as a µf or property
            // TODO: Determine if clearing this is required?
            $this->elementPrefixParsed($subMF, 'h');
            $this->elementPrefixParsed($subMF, 'p');
            $this->elementPrefixParsed($subMF, 'u');
            $this->elementPrefixParsed($subMF, 'dt');
            $this->elementPrefixParsed($subMF, 'e');
        }

        // Handle p-*
        foreach ($this->xpath->query('.//*[contains(concat(" ", @class) ," p-")]', $e) as $p) {
            if ($this->isElementParsed($p, 'p'))
                continue;

            $pValue = $this->parseP($p);
            
            // Add the value to the array for it’s p- properties
            foreach (self::mfNamesFromElement($p, 'p-') as $propName) {
                if (!empty($propName))
                    $return[$propName][] = $pValue;
            }
            
            // Make sure this sub-mf won’t get parsed as a top level mf
            $this->elementPrefixParsed($p, 'p');
        }

        // Handle u-*
        foreach ($this->xpath->query('.//*[contains(@class,"u-")]', $e) as $u) {
            if ($this->isElementParsed($u, 'u'))
                continue;

            $uValue = $this->parseU($u);

            // Add the value to the array for it’s property types
            foreach (self::mfNamesFromElement($u, 'u-') as $propName) {
                $return[$propName][] = $uValue;
            }

            // Make sure this sub-mf won’t get parsed as a top level mf
            $this->elementPrefixParsed($u, 'u');
        }

        // Handle dt-*
        foreach ($this->xpath->query('.//*[contains(concat(" ", @class), " dt-")]', $e) as $dt) {
            if ($this->isElementParsed($dt, 'dt'))
                continue;

            $dtValue = $this->parseDT($dt);

            if ($dtValue) {
                // Add the value to the array for dt- properties
                foreach (self::mfNamesFromElement($dt, 'dt-') as $propName) {
                    $return[$propName][] = $dtValue;
                }
            }
            
            // Make sure this sub-mf won’t get parsed as a top level mf
            $this->elementPrefixParsed($dt, 'dt');
        }

        // TODO: Handle e-* (em)
        foreach ($this->xpath->query('.//*[contains(concat(" ", @class)," e-")]', $e) as $em) {
            if ($this->isElementParsed($e, 'e'))
                continue;

            $eValue = $this->parseE($em);

            if ($eValue) {
                // Add the value to the array for e- properties
                foreach (self::mfNamesFromElement($em, 'e-') as $propName) {
                    $return[$propName][] = $eValue;
                }
            }
            // Make sure this sub-mf won’t get parsed as a top level mf
            $em->setAttribute('data-e-parsed', 'true');
        }

        // !Implied Properties
        // Check for p-name
        if (!array_key_exists('name', $return)) {
            try {
                // Look for img @alt
                if ($e->tagName == 'img' and $e->getAttribute('alt') != '')
                    throw new Exception($e->getAttribute('alt'));

                // Look for nested img @alt
                foreach ($this->xpath->query('./img[count(preceding-sibling::*)+count(following-sibling::*)=0]', $e) as $em) {
                    if ($em->getAttribute('alt') != '')
                        throw new Exception($em->getAttribute('alt'));
                }

                // Look for double nested img @alt
                foreach ($this->xpath->query('./*[count(preceding-sibling::*)+count(following-sibling::*)=0]/img[count(preceding-sibling::*)+count(following-sibling::*)=0]', $e) as $em) {
                    if ($em->getAttribute('alt') != '')
                        throw new Exception($em->getAttribute('alt'));
                }

                throw new Exception(trim($e->nodeValue));
            } catch (Exception $exc) {
                $return['name'][] = $exc->getMessage();
            }
        }

        // Check for u-photo
        if (!array_key_exists('photo', $return)) {
            // Look for img @src
            try {
                if ($e->tagName == 'img')
                    throw new Exception($e->getAttribute('src'));

                // Look for nested img @src
                foreach ($this->xpath->query('./img[count(preceding-sibling::img)+count(following-sibling::img)=0]', $e) as $em) {
                    if ($em->getAttribute('src') != '')
                        throw new Exception($em->getAttribute('src'));
                }

                // Look for double nested img @src
                foreach ($this->xpath->query('./*[count(preceding-sibling::img)+count(following-sibling::img)=0]/img[count(preceding-sibling::img)+count(following-sibling::img)=0]', $e) as $em) {
                    if ($em->getAttribute('src') != '')
                        throw new Exception($em->getAttribute('src'));
                }
            } catch (Exception $exc) {
                $return['photo'][] = $exc->getMessage();
            }
        }

        // Check for u-url
        if (!array_key_exists('url', $return)) {
            // Look for img @src
            // @todo resolve relative URLs
            if ($e->tagName == 'a')
                $return['url'][] = $e->getAttribute('href');

            // Look for nested img @src
            foreach ($this->xpath->query('./a[count(preceding-sibling::a)+count(following-sibling::a)=0]', $e) as $em) {
                $return['url'][] = $em->getAttribute('href');
                break;
            }
        }

        // Make sure things are in alphabetical order
        sort($mfTypes);
        
        // Phew. Return the final result.
        $parsed = array(
            'type' => $mfTypes,
            'properties' => $return
        );
        if (!empty($children))
            $parsed['children'] = array_values(array_filter($children));
        return $parsed;
    }

    /**
     * Kicks off the parsing routine
     * 
     * @return array An array containing all the µfs found in the current document
     */
    public function parse() {
        $this->xpath = new DOMXPath($this->doc);
        
        $mfs = array();

        foreach ($this->xpath->query('//*[contains(concat(" ",  @class), " h-")]') as $node) {
            // For each microformat
            $result = $this->parseH($node);

            // Add the value to the array for this property type
            $mfs[] = $result;
        }

        return array('items' => array_values(array_filter($mfs)));
    }

    /**
     * Convert Legacy Classnames
     * 
     * Adds microformats-2 classnames into a document containing only legacy
     * semantic classnames. By default performs classic microformat conversion,
     * but other builtin/arbitrary classmaps can be added.
     * 
     * @return Parser $this
     */
    public function convertLegacy() {
        $map = $this->classicMap;
        
        $doc = $this->doc;
        
        $xp = new DOMXPath($doc);
        
        foreach ($map as $old => $new) {
            // Find all elements with .old but not .new
            foreach ($xp->query('//*[contains(concat(" ", @class, " "), " ' . $old . ' ") and not(contains(concat(" ", @class, " "), " ' . $new . ' "))]') as $el) {
                $el->setAttribute('class', $el->getAttribute('class') . ' ' . $new);
            }
        }
        
        return $this;
    }
    
    /**
     * Add Class Map
     * 
     * Adds a mapping of legacy classes to microformats-2 classes to replace them
     * with. These are converted when <code>convertLegacy()</code> is called.
     * 
     * @param array $map
     * @return \mf2\Parser
     */
    public function addClassMap(array $map) {
        $this->classicMap = array_merge($this->classicMap, $map);
        return $this;
    }
    
    /**
     * Add Twitter Class Map
     * 
     * Adds a mapping of twitter.com classnames -> microformats 2 classnames.
     * Converted when <code>convertLegacy()</code> is called.
     * 
     * @return \mf2\Parser
     */
    public function addTwitterClassMap() {
        $this->addClassMap($this->twitterMap);
        return $this;
    }
    
    /**
     * A mapping of classnames found on twitter.com to their microformats-2 
     * equivalents.
     * 
     * @var array
     */
    public $twitterMap = array(
        // Tweet Page
        'stream-uncapped' => 'h-feed',
        'tweet' => 'h-entry',
        'js-tweet-text' => 'p-content',
        'twitter-atreply' => 'h-x-username',
        'account-group' => 'h-card p-author',
        'avatar' => 'u-photo',
        'fullname' => 'p-name',
        'username' => 'p-nickname',
        'js-permalink' => 'u-url',
        'replies-to' => 'h-feed h-x-replies',
        'js-user-profile-link' => 'u-url',
        // User Page
        'profile-card' => 'h-card',
        'bio' => 'p-note',
        'location' => 'p-location h-adr',
    );
    
    /**
     * Classic Microformats Map
     * 
     * Maps classic classnames to their µf2 equivalents
     */
    public $classicMap = array(
        // hCard (inc. h-adr and h-geo)
        'vcard' => 'h-card',
        'fn' => 'p-name',
        'url' => 'u-url',
        'honorific-prefix' => 'p-honorific-prefix',
        'given-name' => 'p-given-name',
        'additional-name' => 'p-additional-name',
        'family-name' => 'p-family-name',
        'honorific-suffix' => 'p-honorific-suffix',
        'nickname' => 'p-nickname',
        'email' => 'u-email',
        'logo' => 'u-logo',
        'photo' => 'u-photo',
        'url' => 'u-url',
        'uid' => 'u-uid',
        'category' => 'p-category',
        'adr' => 'p-adr h-adr',
        'extended-address' => 'p-extended-address',
        'street-address' => 'p-street-address',
        'locality' => 'p-locality',
        'region' => 'p-region',
        'postal-code' => 'p-postal-code',
        'country-name' => 'p-country-name',
        'label' => 'p-label',
        'geo' => 'p-geo h-geo',
        'latitude' => 'p-latitude',
        'longitude' => 'p-longitude',
        'tel' => 'p-tel',
        'note' => 'p-note',
        'bday' => 'dt-bday',
        'key' => 'u-key',
        'org' => 'p-org',
        'organization-name' => 'p-organization-name',
        'organization-unit' => 'p-organization-unit',
        // hAtom
        'hfeed' => 'h-feed',
        'hentry' => 'h-entry',
        'entry-title' => 'p-name',
        'entry-summary' => 'p-summary',
        'entry-content' => 'e-content',
        'published' => 'dt-published',
        'updated' => 'dt-updated',
        'author' => 'p-author h-card',
        'category' => 'p-category',
        'geo' => 'p-geo h-geo',
        'latitude' => 'p-latitude',
        'longitude' => 'p-longitude',
        // hRecipe
        'ingredient' => 'p-ingredient',
        'yield' => 'p-yield',
        'instructions' => 'e-instructions',
        'duration' => 'dt-duration',
        'nutrition' => 'p-nutrition',
        // hResume
        'contact' => 'h-card p-contact',
        'education' => 'h-event p-education',
        'experience' => 'h-event p-experience',
        'skill' => 'p-skill',
        'affiliation' => 'p-affiliation h-card',
        // hEvent
        'dtstart' => 'dt-start',
        'dtend' => 'dt-end',
        'duration' => 'dt-duration',
        'description' => 'p-description'
    );

}

// EOF
