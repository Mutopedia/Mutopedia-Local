<?php

class Tool
{
	public $newbdd;

	static $localisationTXTPath = 'https://s-beta.kobojo.com/mutants/gameconfig/localisation_en.txt';
	static $gamedefinitionsXMLPath = 'https://s-beta.kobojo.com/mutants/gameconfig/gamedefinitions.xml';
	static $spritesXMLPath = 'https://s-beta.kobojo.com/mutants/gameconfig/sprites.xml';
	static $bigDNAPNG = "https://s-ak.kobojo.com/mutants/assets/genes/";

	public function __construct()
	{

	}

	public static function init()
	{
		$specimenList = self::listSpecimen();
		$_SESSION['specimenList'] = $specimenList;

		return $_SESSION['specimenList'];
	}

	public static function listSpecimen(){
		$specimenCount = 0;
		$dataArray[$specimenCount]['nameCode'] = array();
		$dataArray[$specimenCount]['name'] = array();

		$xmlDoc = new DOMDocument();

		if(@$xmlDoc->load(self::$gamedefinitionsXMLPath) === false)
		{
			$dataArray = null;
		}
		else
		{
			$xpath = new DOMXpath($xmlDoc);

			$specimen = $xmlDoc->getElementsByTagName("DynamicEntities")->item(0)->getElementsByTagName("EntityDescriptor");

			$specimenList = file_get_contents(self::$localisationTXTPath);

			$specimenCount = 0;

			foreach($specimen as $specimen)
			{
				if($specimen->getAttribute('category') == "specimen")
				{
					$char_pos = 0;

					while(($char_pos = strpos($specimenList, $specimen->getAttribute('id'), $char_pos)) !== false)
					{
						$firstCharPos = $char_pos - 1;
						if($specimenList[$firstCharPos] !== '-' AND $specimenList[$firstCharPos] !== '_')
						{
							$nameCode = "";
							$semiColonPos = $char_pos;
							while($specimenList[$semiColonPos] != ";")
							{
								$nameCode .= $specimenList[$semiColonPos];
								$semiColonPos++;
							}

							if($nameCode == $specimen->getAttribute('id'))
							{
								$name = "";
								$namePos = $semiColonPos + 1;
								while($specimenList[$namePos] != "\n")
								{
									$name .= $specimenList[$namePos];
									$namePos++;
								}

								$dataArray[$specimenCount]['nameCode'][$specimenCount] = $specimen->getAttribute('id');
								$dataArray[$specimenCount]['name'][$specimenCount] = $name;

								$specimenDNA_query = $xpath->query('//EntityDescriptor[@id="'.$dataArray[$specimenCount]['nameCode'][$specimenCount].'"]/Tag[@key="dna"]/@value')->item(0);
								$specimen_DNA = $specimenDNA_query->value;
								$specimen_DNA_split = str_split($specimen_DNA);

								$dataArray[$specimenCount]['dna_0'][$specimenCount] = $specimen_DNA_split[0];
								if(count($specimen_DNA_split) == 2)
								{
									$dataArray[$specimenCount]['dna_1'][$specimenCount] = $specimen_DNA_split[1];
								}
								else
								{
									$dataArray[$specimenCount]['dna_1'][$specimenCount] = null;
								}

								$specimenCount++;
							}
						}

						$char_pos++;
					}
				}
			}
		}

		return $dataArray;
	}

	public static function getSpecimens(){
		$returnSpecimen = $_SESSION['specimenList'];

		if($returnSpecimen !== null)
		{
			$result_pos = "";

			$countSpecimen = 0;
			while($countSpecimen < count($returnSpecimen))
			{
				$mutantNameCode = $returnSpecimen[$countSpecimen]['nameCode'][$countSpecimen];
				$mutantName = $returnSpecimen[$countSpecimen]['name'][$countSpecimen];
				$mutantIconDNA_0 = self::getIconDNA($returnSpecimen[$countSpecimen]['dna_0'][$countSpecimen]);
				$mutantIconDNA_1 = "";
				if(!empty($returnSpecimen[$countSpecimen]['dna_1'][$countSpecimen]))
				{
					$mutantIconDNA_1 = self::getIconDNA($returnSpecimen[$countSpecimen]['dna_1'][$countSpecimen]);
				}

				ob_start();
				include('../models/specimen_list.php');
				$result_pos .= ob_get_contents();
				ob_end_clean();
				$countSpecimen++;
			}

			return $result_pos;
		}
		else
		{
			return "Unable to retrieve the list of mutants ...";
		}
	}

	public static function searchSpecimen($specimenName){
		$returnSpecimen = $_SESSION['specimenList'];

		if($returnSpecimen == null)
		{
			$dataArray['reply'] = null;
			$dataArray['result'] = false;
			$dataArray['error'] = "Unable to retrieve the list of mutants ...";
		}
		else
		{
			if(isset($specimenName) && !empty($specimenName))
			{
				$countSpecimen = 0;
				while($countSpecimen < count($returnSpecimen))
				{
					$mutantNameCode = $returnSpecimen[$countSpecimen]['nameCode'][$countSpecimen];
					$mutantName = $returnSpecimen[$countSpecimen]['name'][$countSpecimen];

					if(strpos(strtolower($mutantName), strtolower($specimenName)) !== false)
					{
						$mutantIconDNA_0 = self::getIconDNA($returnSpecimen[$countSpecimen]['dna_0'][$countSpecimen]);
						$mutantIconDNA_1 = "";
						if(!empty($returnSpecimen[$countSpecimen]['dna_1'][$countSpecimen]))
						{
							$mutantIconDNA_1 = self::getIconDNA($returnSpecimen[$countSpecimen]['dna_1'][$countSpecimen]);
						}

						ob_start();
						include('../models/specimen_list.php');
						$dataArray['reply'] .= ob_get_contents();
						ob_end_clean();
					}
					$countSpecimen++;
				}

				$dataArray['result'] = true;
				$dataArray['error'] = null;
			}
			else
			{
				$dataArray['reply'] = self::getSpecimens();
				$dataArray['result'] = true;
				$dataArray['error'] = "specimenName is empty or invalid";
			}
		}

		return $dataArray;
	}

	public static function startBreeding($specimenNameCode_1, $specimenNameCode_2){
		$dataArray['reply'] = "";

		$xmlDoc = new DOMDocument();

		if(@$xmlDoc->load(self::$gamedefinitionsXMLPath) === false)
		{
			$dataArray['reply'] = null;
			$dataArray['result'] = false;
			$dataArray['error'] = "Unable to retrieve the list of mutants ...";
		}
		else
		{
			$xpath = new DOMXpath($xmlDoc);

			$breedingLevel = 1;
			$ODD_final = 0;
			$isDoubleGene = false;

			$specimen_1 = $xmlDoc->getElementsByTagName("DynamicEntities")->item(0)->getElementsByTagName("EntityDescriptor");
			$specimen_1_Code = "";
			$specimen_1_ODD = "";
			$specimen_1_DNA = "";
			$specimen_1_TYPE = "";

			foreach($specimen_1 as $specimen_1)
			{
				$valueId = $specimen_1->getAttribute('id');

				if(strpos($valueId, $specimenNameCode_1) !== false)
				{
					$specimen_1_Code = $valueId;

					$specimenODD_query = $xpath->query('//EntityDescriptor[@id="'.$specimen_1_Code.'"]/Tag[@key="odds"]/@value')->item(0);
					$specimen_1_ODD = $specimenODD_query->value;

					$specimenDNA_query = $xpath->query('//EntityDescriptor[@id="'.$specimen_1_Code.'"]/Tag[@key="dna"]/@value')->item(0);
					$specimen_1_DNA = $specimenDNA_query->value;
					$specimen_1_DNA_split = str_split($specimen_1_DNA);

					/*if($specimenTYPE_query = $xpath->query('//EntityDescriptor[@id="'.$specimen_1_Code.'"]/Tag[@key="type"]/@value')->item(0) !== false)
					{
						$specimen_1_TYPE = $specimenTYPE_query->value;
					}
					else
					{*/
						$specimen_1_TYPE = "NORMAL";
					/*}*/
				}
			}

			$specimen_2 = $xmlDoc->getElementsByTagName("DynamicEntities")->item(0)->getElementsByTagName("EntityDescriptor");
			$specimen_2_Code = "";
			$specimen_2_ODD = "";
			$specimen_2_DNA = "";
			$specimen_2_TYPE = "";

			foreach($specimen_2 as $specimen_2)
			{
				$valueId = $specimen_2->getAttribute('id');

				if(strpos($valueId, $specimenNameCode_2) !== false)
				{
					$specimen_2_Code = $valueId;

					$specimenODD_query = $xpath->query('//EntityDescriptor[@id="'.$specimen_2_Code.'"]/Tag[@key="odds"]/@value')->item(0);
					$specimen_2_ODD = $specimenODD_query->value;

					$specimenDNA_query = $xpath->query('//EntityDescriptor[@id="'.$specimen_2_Code.'"]/Tag[@key="dna"]/@value')->item(0);
					$specimen_2_DNA = $specimenDNA_query->value;
					$specimen_2_DNA_split = str_split($specimen_2_DNA);

					if($specimenTYPE_query = $xpath->query('//EntityDescriptor[@id="'.$specimen_2_Code.'"]/Tag[@key="type"]/@value')->item(0))
					{
						$specimen_2_TYPE = $specimenTYPE_query->value;
					}
					else
					{
						$specimen_2_TYPE = "NORMAL";
					}
				}
			}

			$specimen_1_DNA_lenght = strlen($specimen_1_DNA);
			$specimen_2_DNA_lenght = strlen($specimen_2_DNA);

			$resultDNA = array();

			if($specimen_1_DNA_lenght == 2 AND $specimen_2_DNA_lenght == 2)
			{
				$resultDNA[0] = $specimen_1_DNA_split[0].$specimen_2_DNA_split[0]."_01";
				$resultDNA[1] = $specimen_1_DNA_split[0].$specimen_2_DNA_split[1]."_01";

				$resultDNA[2] = $specimen_1_DNA_split[1].$specimen_2_DNA_split[0]."_01";
				$resultDNA[3] = $specimen_1_DNA_split[1].$specimen_2_DNA_split[1]."_01";

				$resultDNA[4] = $specimen_2_DNA_split[0].$specimen_1_DNA_split[0]."_01";
				$resultDNA[5] = $specimen_2_DNA_split[0].$specimen_1_DNA_split[1]."_01";

				$resultDNA[6] = $specimen_2_DNA_split[1].$specimen_1_DNA_split[0]."_01";
				$resultDNA[7] = $specimen_2_DNA_split[1].$specimen_1_DNA_split[1]."_01";
			}
			else if($specimen_1_DNA_lenght == 1 AND $specimen_2_DNA_lenght == 2)
			{
				$resultDNA[0] = $specimen_2_DNA_split[0].$specimen_1_DNA_split[0]."_01";
				$resultDNA[1] = $specimen_2_DNA_split[1].$specimen_1_DNA_split[0]."_01";

				$resultDNA[2] = $specimen_1_DNA_split[0].$specimen_2_DNA_split[0]."_01";
				$resultDNA[3] = $specimen_1_DNA_split[0].$specimen_2_DNA_split[1]."_01";

				if(($specimen_1_DNA_split[0] == $specimen_2_DNA_split[0]) OR ($specimen_1_DNA_split[0] == $specimen_2_DNA_split[1]))
				{
					$resultDNA[4] = $specimen_1_DNA_split[0]."_01";
				}
			}
			else if($specimen_1_DNA_lenght == 2 AND $specimen_2_DNA_lenght == 1)
			{
				$resultDNA[0] = $specimen_1_DNA_split[0].$specimen_2_DNA_split[0]."_01";
				$resultDNA[1] = $specimen_1_DNA_split[1].$specimen_2_DNA_split[0]."_01";

				$resultDNA[2] = $specimen_2_DNA_split[0].$specimen_1_DNA_split[0]."_01";
				$resultDNA[3] = $specimen_2_DNA_split[0].$specimen_1_DNA_split[1]."_01";

				if(($specimen_2_DNA_split[0] == $specimen_1_DNA_split[0]) OR ($specimen_2_DNA_split[0] == $specimen_1_DNA_split[1]))
				{
					$resultDNA[4] = $specimen_2_DNA_split[0]."_01";
				}
			}
			else if($specimen_1_DNA_lenght == 1 AND $specimen_2_DNA_lenght == 1)
			{
				$resultDNA[0] = $specimen_1_DNA_split[0].$specimen_2_DNA_split[0]."_01";
				$resultDNA[1] = $specimen_2_DNA_split[0].$specimen_1_DNA_split[0]."_01";

				if(($specimen_1_DNA_split[0] == $specimen_2_DNA_split[0]))
				{
					$resultDNA[2] = $specimen_1_DNA_split[0]."_01";
					$resultDNA[3] = $specimen_2_DNA_split[0]."_01";
				}
			}

			$resultDNA = array_unique($resultDNA);

			$resultSpecimenODD = array();
			$resultSpecimenName = array();
			$resultSpecimenCount = 0;
			$total_ODD = 0;

			foreach($resultDNA as $key => $value)
			{
				$specimenODD_query = $xpath->query('//EntityDescriptor[@id="Specimen_'.$value.'"]/Tag[@key="odds"]/@value')->item(0);
				$specimenResult_ODD = $specimenODD_query->value;

				$specimenDNA_query = $xpath->query('//EntityDescriptor[@id="Specimen_'.$value.'"]/Tag[@key="dna"]/@value')->item(0);
				$specimenResult_DNA = $specimenDNA_query->value;

				if(($specimen_1_DNA_lenght == 1 AND $specimen_2_DNA_lenght == 2) OR ($specimen_1_DNA_lenght == 2 AND $specimen_2_DNA_lenght == 1) OR ($specimen_1_DNA_lenght == 2 AND $specimen_2_DNA_lenght == 2))
				{
					if(strlen($specimenResult_DNA) == 2)
					{
						$specimenResult_ODD = $specimenResult_ODD * 18;
					}
				}
				else if($specimen_1_DNA_lenght == 1 AND $specimen_2_DNA_lenght == 1)
				{
					if(strlen($specimenResult_DNA) == 2)
					{
						$specimenResult_ODD = $specimenResult_ODD * 4;
					}
				}

				$resultSpecimenODD[$resultSpecimenCount] = $specimenResult_ODD;
				$resultSpecimenName[$resultSpecimenCount] = self::findSpecimenName('Specimen_'.$value);
				$total_ODD = $total_ODD + $resultSpecimenODD[$resultSpecimenCount];

				$resultSpecimenCount++;
			}

			$resultCount = 0;
			while ($resultCount < $resultSpecimenCount)
			{
				$specimenName = $resultSpecimenName[$resultCount];
				$specimenODD = $resultSpecimenODD[$resultCount];
				$specimenPercent = round(($resultSpecimenODD[$resultCount] / $total_ODD) * 100, 1);

				ob_start();
				include('../models/mutant_container.php');
				$dataArray['reply'] .= ob_get_contents();
				ob_end_clean();

				$resultCount++;
			}

			/*$dataArray['reply'] = $resultDNA;*/
			$dataArray['result'] = true;
			$dataArray['error'] = null;
		}

		return $dataArray;
	}

	public static function findSpecimenName($nameCode)
	{
		$returnSpecimen = $_SESSION['specimenList'];

		if(isset($nameCode) && !empty($nameCode))
		{
			$countSpecimen = 0;
			while($countSpecimen < count($returnSpecimen))
			{
				$mutantNameCode = $returnSpecimen[$countSpecimen]['nameCode'][$countSpecimen];
				$mutantName = $returnSpecimen[$countSpecimen]['name'][$countSpecimen];

				if(strpos(strtolower($mutantNameCode), strtolower($nameCode)) !== false)
				{
					return $mutantName;
				}
				$countSpecimen++;
			}
		}
	}

	public static function findSpecimenNameCode($specimenName)
	{
		$returnSpecimen = $_SESSION['specimenList'];

		$dataArray = array();

		$arrayId = 0;

		if(isset($specimenName) && !empty($specimenName))
		{
			$countSpecimen = 0;
			while($countSpecimen < count($returnSpecimen))
			{
				$mutantNameCode = $returnSpecimen[$countSpecimen]['nameCode'][$countSpecimen];
				$mutantName = $returnSpecimen[$countSpecimen]['name'][$countSpecimen];

				$dataArray[$arrayId] = null;
				if(strpos(strtolower($mutantName), strtolower($specimenName)) !== false)
				{
					$dataArray[$arrayId] = $mutantNameCode;
					$arrayId++;
				}
				$countSpecimen++;
			}
		}

		return $dataArray;
	}

	public static function getSpecimenDNA($nameCode)
	{
		$returnSpecimen = $_SESSION['specimenList'];

		if(isset($nameCode) && !empty($nameCode))
		{
			$xmlDoc = new DOMDocument();
			$xmlDoc->load(self::$gamedefinitionsXMLPath);

			$xpath = new DOMXpath($xmlDoc);

			$specimenDNA_query = $xpath->query('//EntityDescriptor[@id="'.$nameCode.'"]/Tag[@key="dna"]/@value')->item(0);
			$specimen_DNA = $specimenDNA_query->value;
			$specimen_DNA_split = str_split($specimen_DNA);

			return $specimen_DNA_split;
		}
	}

	public static function getIconDNA($DNA)
	{
		if(isset($DNA) && !empty($DNA))
		{
			switch ($DNA) {
				case "A":
					return self::$bigDNAPNG."big_a.png";
					break;
				case "B":
					return self::$bigDNAPNG."big_b.png";
					break;
				case "C":
					return self::$bigDNAPNG."big_c.png";
					break;
				case "D":
					return self::$bigDNAPNG."big_d.png";
					break;
				case "E":
					return self::$bigDNAPNG."big_e.png";
					break;
				case "F":
					return self::$bigDNAPNG."big_f.png";
					break;
			}
		}
	}

	public static function getSpecimenSprite($specimenCode){
		$dataArray = array();

		if(empty($specimenCode) OR !isset($specimenCode))
		{
			$dataArray['reply'] = null;
		}
		else
		{
			$xmlDoc = new DOMDocument();

			if(@$xmlDoc->load(self::$spritesXMLPath) === false)
			{
				$dataArray['reply'] = null;
			}
			else
			{
				$xpath = new DOMXpath($xmlDoc);

				$specimenCode = lcfirst($specimenCode);
				$specimenTags = $xmlDoc->getElementsByTagName("Sprite")->item(0);

			/*	$specimenBitmapValue = $xpath->query("/@bitmap", $specimenTags)->value;

				$dataArray['reply'] .= 'Image bitmap of '.$specimenCode.'_stand : '.$specimenBitmapValue;*/

				/*$dataArray['reply'] .= 'Image from Composite :';

				$specimenImage_srcX_value = $specimenTags->getElementsByTagName("Composite")->item(0)->getElementsByTagName("Sprite")->item(0)->getElementsByTagName("Image")->getAttribute('srcX');

				$dataArray['reply'] .= 'srcX= '.$specimenImage_srcX_value;*/
			}
		}

		return $dataArray;
	}
}

?>
