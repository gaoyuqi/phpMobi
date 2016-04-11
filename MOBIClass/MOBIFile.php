<?php
/**
 * This is the way MOBI files should be created if you want all features (TOC, images).
 *
 * File modified by Dawson for use in eBook Creator
 * Added pagebreaks and a setting to remove table of contents.
 */

class MOBIFile extends ContentProvider {
	const PARAGRAPH = 0;
	const H2 = 1;
	const H3 = 2;
	const IMAGE = 3;
	const PAGEBREAK = 4;
	
	private $settings = array("title" => "Unknown Title", "toc" => true);
	private $parts = array();
	private $images = array();
	
	/**
	 * Get the text data (the "html" code)
	 */
	public function getTextData(){
		$prefix = "<html><head><guide><reference title='CONTENT' type='toc' filepos=0000000000 /></guide></head><body>";
		
		$title = "<h1>".$this->settings["title"]."</h1>";
		
		list($text, $entries) = $this->generateText();
		
		if($this->settings["toc"]) {
			$toc = $this->generateTOC($entries); //Generate TOC to get the right length
			$tocv2 = $this->generateTOC($entries, strlen($prefix)+strlen($toc)+strlen($title)); //Generate the real TOC
			
			if (strlen($toc) != strlen($tocv2)) {
				throw new Exception("Error while generating TOC");
			}
			
			$toc = $tocv2;
		}

		$suffix = "</body></html>";
		
		return $prefix.$toc.$title.$text.$suffix;
	}
	
	/**
	 * Generate the body's text and the chapter entries
	 * @return array($string, $entries) $string is the html data, $entries
	 * contains the level, the title and the position of the titles.
	 */
	public function generateText(){
		$str = array();
		$entries = array();
		
		$length_until_now = 0;
		
		for($i = 0; $i < sizeof($this->parts); $i++){
			list($type, $data) = $this->parts[$i];
			$id = "title_".$i;
			
			$cur_str = "";
			switch($type){
				case self::PARAGRAPH:
					$cur_str = "<p>".$data."</p>";
					break;
				case self::PAGEBREAK:
					$cur_str = '<mbp:pagebreak/>';
					break;
				case self::H2:
					$entries[] = array("level" => 2, "position" => $length_until_now, "title" => $data, "id" => $id);
					$cur_str = "<a name='".$id."'></a><h2 id='" . $id . "'>".$data."</h2>";
					break;
				case self::H3:
					$entries[] = array("level" => 3, "position" => $length_until_now, "title" => $data, "id" => $id);
					$cur_str = "<h3 id='" . $id . "'>".$data."</h3>";
					break;
				case self::IMAGE:
					$cur_str = "<img recindex=".str_pad($data+1, 10, "0", STR_PAD_LEFT)." />";
					break;
			}
			
			$length_until_now += strlen($cur_str);
			$str[] = $cur_str;
		}
		return array(implode("", $str), $entries);
	}
	
	/**
	 * Generate a TOC
	 * @param $entries The entries array generated by generateText
	 * @param $base The zero position
	 */
	public function generateTOC($entries, $base = 0){
		$toc = "<h2>Contents</h2>";
		$toc .= "<blockquote><table summary='Table of Contents'><col/><tbody>";
		for($i = 0, $len = sizeof($entries); $i < $len; $i++){
			$entry = $entries[$i];
			$pos = str_pad($entry["position"]+$base, 10, "0", STR_PAD_LEFT);
			$toc .= "<tr><td><a href='#".$entry["id"]."' filepos=".$pos.">".$entry["title"]."</a></td></tr>";
		}
		$toc .= "</tbody></b></table></blockquote><mbp:pagebreak/>";
		
		return $toc;
	}
	
	/**
	 * Get the file records of the images
	 */
	public function getImages(){
		return $this->images;
	}
	
	/**
	 * Get the metadata
	 */
	public function getMetaData(){
		return $this->settings;
	}
	
	/**
	 * Change the file's settings. For example set("author", "John Doe") or set("title", "The adventures of John Doe").
	 * @param $key Key of the setting to insert.
	 */
	public function set($key, $value){
		$this->settings[$key] = $value;
	}
	
	/**
	 * Get the file's settings.
	 */
	public function get($key){
		return $this->settings[$key];
	}
	
	/**
	 * Append a paragraph of text to the file.
	 * @param string $text The text to insert.
	 */
	public function appendParagraph($text){
		$this->parts[] = array(self::PARAGRAPH, $text);
	}
	
	/**
	 * Append a chapter title (H2)
	 * @param string $title The title to insert.
	 */
	public function appendChapterTitle($title){
		$this->parts[] = array(self::H2, $title);
	}
	
	/**
	 * Append a section title (H3)
	 * @param string $title The title to insert.
	 */
	public function appendSectionTitle($title){
		$this->parts[] = array(self::H3, $title);
	}
	
	public function appendPageBreak() {
		$this->parts[] = array(self::PAGEBREAK, null);
	}

	/**
	 * Append an image.
	 * @param resource $img An image file (for example, created by `imagecreate`)
	 */
	public function appendImage($img){
		$imgIndex = sizeof($this->images);
		$this->images[] = new FileRecord(new Record(ImageHandler::CreateImage($img)));
		$this->parts[] = array(self::IMAGE, $imgIndex);
	}
}