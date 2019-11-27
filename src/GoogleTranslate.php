<?php
namespace Statickidz;

/**
 * GoogleTranslate.class.php
 *
 * Class to talk with Google Translator for free.
 *
 * @package PHP Google Translate Free;
 * @category Translation
 * @author Adrián Barrio Andrés
 * @author Paris N. Baltazar Salguero <sieg.sb@gmail.com>
 * @copyright 2016 Adrián Barrio Andrés
 * @license https://opensource.org/licenses/GPL-3.0 GNU General Public License 3.0
 * @version 2.0
 * @link https://statickidz.com/
 */

/**
 * Main class GoogleTranslate
 *
 * @package GoogleTranslate
 *
 */
class GoogleTranslate
{
    //Setting up a property for translating method, default is GOOGLE
	protected $method = "GOOGLE";
    
    /**
	 * Method for setting $method
	 *
	 *
	 * @param string $method_name
	 *            name of translating method, could be either GOOGLE or FANYI
	 *
	 * @return boolen true for success, false for failure
	 */
	public function setMethod($method_name) {
		if ($method_name !== "GOOGLE" || $method_name !== "FANYI") {
			return false;
		}
		$this->method = $method_name;
		return true;
	}
    
    /**
     * Retrieves the translation of a text
     * Implement the recovery through the tor, removing limitations of request.
     *
     * @param string $source
     *            Original language of the text on notation xx. For example: es, en, it, fr...
     * @param string $target
     *            Language to which you want to translate the text in format xx. For example: es, en, it, fr...
     * @param string $text
     *            Text that you want to translate
     *
     * @return string a simple string with the translation of the text in the target language
     */
    public function translateGhost($source, $target, $text) // static removed for in order to use object's $method property
    {
       //addind swtich to judge which translation method should be used
		switch ($this->method) {
		case "GOOGLE":
            //The original google translation
			$response = self::requestTranslationGhost($source, $target, $text);
			$translation = self::getSentencesFromJSON($response);
			break;
		case "FANYI":
            //Youdao's fanyi service
			$response = self::requestTranslationFanyi($source, $target, $text);
			$translation = self::getSentencesFromFanyiJSON($response);
			break;
		}

		return $translation;
    }
    /**
     * Internal function to make the request to the translator service
     *
     * @internal
     *
     * @param string $source
     *            Original language taken from the 'translate' function
     * @param string $target
     *            Target language taken from the ' translate' function
     * @param string $text
     *            Text to translate taken from the 'translate' function
     *
     * @return object[] The response of the translation service in JSON format
     */
    protected static function requestTranslationGhost($source, $target, $text)
    {

        // Google translate URL
        $url = "https://translate.google.com/translate_a/single?client=at&dt=t&dt=ld&dt=qca&dt=rm&dt=bd&dj=1&hl=es-ES&ie=UTF-8&oe=UTF-8&inputm=2&otf=2&iid=1dd3b944-fa62-4b55-b330-74909a99969e";

        $fields = array(
            'sl' => urlencode($source),
            'tl' => urlencode($target),
            'q' => urlencode($text)
        );

        if(strlen($fields['q'])>=5000)
            throw new \Exception("Maximum number of characters exceeded: 5000");
        
        // URL-ify the data for the POST
        $fields_string = "";
        foreach ($fields as $key => $value) {
            $fields_string .= $key . '=' . $value . '&';
        }

        rtrim($fields_string, '&');

        // Open connection
        $ch = curl_init();

        // Set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, $url);

        /*
        *
        * We add the proxy created by tor.
        * You only need to have installed tor in our system.
        *
        */
        curl_setopt($ch, CURLOPT_PROXY, "127.0.0.1:9050");
        curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
        
        curl_setopt($ch, CURLOPT_POST, count($fields));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_ENCODING, 'UTF-8');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'AndroidTranslate/5.3.0.RC02.130475354-53000263 5.1 phone TRANSLATE_OPM5_TEST_1');

        // Execute post
        $result = curl_exec($ch);

        /*
        *
        * Check that google sent the json, otherwise modify the ip
        *
        */
        if(count(explode('<A HREF="',$result))>1){
            // Restart the tor service, causing it to assign another ip.
            shell_exec('sudo service tor restart');
            return self::requestTranslation($source, $target, $text);
        }

        // Close connection
        curl_close($ch);

        return $result;
    }
    

    /**
     * Retrieves the translation of a text
     *
     * @param string $source
     *            Original language of the text on notation xx. For example: es, en, it, fr...
     * @param string $target
     *            Language to which you want to translate the text in format xx. For example: es, en, it, fr...
     * @param string $text
     *            Text that you want to translate
     *
     * @return string a simple string with the translation of the text in the target language
     */
    public static function translate($source, $target, $text)
    {
        // Request translation
        $response = self::requestTranslation($source, $target, $text);

        // Clean translation
        $translation = self::getSentencesFromJSON($response);

        return $translation;
    }

    /**
     * Internal function to make the request to the translator service
     *
     * @internal
     *
     * @param string $source
     *            Original language taken from the 'translate' function
     * @param string $target
     *            Target language taken from the ' translate' function
     * @param string $text
     *            Text to translate taken from the 'translate' function
     *
     * @return object[] The response of the translation service in JSON format
     */
    protected static function requestTranslation($source, $target, $text)
    {

        // Google translate URL
        $url = "https://translate.google.com/translate_a/single?client=at&dt=t&dt=ld&dt=qca&dt=rm&dt=bd&dj=1&hl=es-ES&ie=UTF-8&oe=UTF-8&inputm=2&otf=2&iid=1dd3b944-fa62-4b55-b330-74909a99969e";

        $fields = array(
            'sl' => urlencode($source),
            'tl' => urlencode($target),
            'q' => urlencode($text)
        );

        if(strlen($fields['q'])>=5000)
            throw new \Exception("Maximum number of characters exceeded: 5000");
        
        // URL-ify the data for the POST
        $fields_string = "";
        foreach ($fields as $key => $value) {
            $fields_string .= $key . '=' . $value . '&';
        }

        rtrim($fields_string, '&');

        // Open connection
        $ch = curl_init();

        // Set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, count($fields));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_ENCODING, 'UTF-8');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'AndroidTranslate/5.3.0.RC02.130475354-53000263 5.1 phone TRANSLATE_OPM5_TEST_1');

        // Execute post
        $result = curl_exec($ch);

        // Close connection
        curl_close($ch);

        return $result;
    }

    /**
     * Dump of the JSON's response in an array
     *
     * @param string $json
     *            The JSON object returned by the request function
     *
     * @return string A single string with the translation
     */
    protected static function getSentencesFromJSON($json)
    {
        $sentencesArray = json_decode($json, true);
        $sentences = "";
        
        if(!$sentencesArray)
            throw new \Exception("Google detected unusual traffic from your computer network, try again later (2 - 48 hours)");

        foreach ($sentencesArray["sentences"] as $s) {
            $sentences .= isset($s["trans"]) ? $s["trans"] : '';
        }

        return $sentences;
    }
    
    /**
	 * 使用[有道翻譯]的內部方法
	 *
	 * @internal
	 *
	 * @param string $source
	 *            Original language taken from the 'translate' function
	 * @param string $target
	 *            Target language taken from the ' translate' function
	 * @param string $text
	 *            Text to translate taken from the 'translate' function
	 *
	 * @return object[] The response of the translation service in JSON format
	 */

	protected static function requestTranslationFanyi($source, $target, $text) {
		// Fanyi translate URL
		$url = "http://fanyi.youdao.com/translate?smartresult=dict&smartresult=rule&sessionFrom=null";
        		
        // Here is the code to bypass Youdao's checking mechanism
        // salt is it's md5 check seed, created by timetamp (in microsconds)
        // adding one random number between 1,10 at the last oct bit.
        $salt_seed = rand(1, 10);
		$ts = (int) (microtime(TRUE) * 1000);
		$salt = "{$ts}{$salt_seed}";
        
        //random text for md5 check, this seems to be changing periodically. Not sure.
        $randomtext="n%A-rKaT5fb[Gy?;N5@Tj";
        
        //sign is calculated by combining keyword: fanyideskweb, $salt, $text, and one specific random text
		$sign = md5("fanyideskweb{$salt}{$text}{$randomtext}");

        
		$fields = [
			'i' => urlencode($text), //translation text, same as GOOGLE
			'from' => urlencode($source), //from language, same as GOOGLE
			'to' => urlencode($target), //to language, same as GOOGLE
			'smartresult' => urlencode("dict"),
			'client' => urlencode("fanyideskweb"),
			'salt' => urlencode($salt),
			'sign' => urlencode($sign),
			'doctype' => urlencode("json"),
			'version' => urlencode("2.1"),
			'keyfrom' => urlencode("fanyi.web"),
			'action' => urlencode("FY_BY_CL1CKBUTTON"),
		];

		if (strlen($fields['i']) >= 5000) {
			throw new \Exception("Exceeding Limit: 5000");
		}

		// URL-ify the data for the POST
		$fields_string = "";
		foreach ($fields as $key => $value) {
			$fields_string .= $key . '=' . $value . '&';
		}

		rtrim($fields_string, '&');

		// Open connection
		$ch = curl_init();

		// Set the url, number of POST vars, POST data
		curl_setopt($ch, CURLOPT_URL, $url);
		/* Using TOR proxy code
		*/
		curl_setopt($ch, CURLOPT_PROXY, "127.0.0.1:9050");
		curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);

		curl_setopt($ch, CURLOPT_POST, count($fields));
		curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_ENCODING, 'UTF-8');
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/78.0.3904.108 Safari/537.36');

		// Execute post
		$result = curl_exec($ch);
		// Close connection
		curl_close($ch);

		return $result;
	}
    
    /**
	 * Parsing Youdao FANYI's JSON
	 *
	 * @param string $json
	 *            The JSON object returned by the request function
	 *
	 * @return string A single string with the translation
	 */
	protected static function getSentencesFromFanyiJSON($json) {
        // The result sentences was store in "translateResult"
		$sentencesArray = json_decode($json, true)["translateResult"];
		$sentences = "";

		if (!$sentencesArray) {
			throw new \Exception("Something wrong!");
		}

		foreach ($sentencesArray as $s) {
            //"tgt" is the translated result, "src" is the original sentence.
			$sentences .= isset($s["tgt"]) ? $s["tgt"] : '';
		}

		return $sentences;
	}
}
