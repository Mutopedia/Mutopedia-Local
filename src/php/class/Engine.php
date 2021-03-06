<?php

class Engine
{
	public static $dataArray = array();

	public function __construct()
	{
		self::$dataArray = array();
	}

	public static function loadModel($modelName, $argPage)
	{
		$ToolClass = new Tool();

		$modelFilename = '../models/'.$modelName.'.php';

		if(file_exists($modelFilename)){
			self::$dataArray['reply'] = "";
			self::$dataArray['error'] = null;

		  ob_start();
			include($modelFilename);
			self::$dataArray['reply'] .= ob_get_contents();
			ob_end_clean();

			self::$dataArray['result'] = true;
		}else {
			self::$dataArray['result'] = false;
			self::$dataArray['reply'] = null;
			self::$dataArray['error'] = "This model does not exist !";
		}

		return self::$dataArray;
	}

	public static function searchUsers($searchContent, $sortByValue, $limitNumberStart, $limitNumber)
	{
		$newStaticBdd = new BDD();

		if(!empty($searchContent))
		{
			$searchContent = $newStaticBdd->real_escape_string(htmlspecialchars($searchContent));
			$sortByValue = $newStaticBdd->real_escape_string(htmlspecialchars($sortByValue));
			$limitNumberStart = $newStaticBdd->real_escape_string(htmlspecialchars($limitNumberStart));
			$limitNumber = $newStaticBdd->real_escape_string(htmlspecialchars($limitNumber));

			switch ($sortByValue) {
				case "fame-asc":
					$orderBySQL = "fame_level ASC";
					break;
				case "fame-desc":
					$orderBySQL = "fame_level DESC";
					break;

				case "center-asc":
					$orderBySQL = "center_level ASC";
					break;
				case "center-desc":
					$orderBySQL = "center_level DESC";
					break;

				case "mutant-asc":
					$orderBySQL = "user_mutant_namecode ASC";
					break;
				case "mutant-desc":
					$orderBySQL = "user_mutant_namecode DESC";
					break;

				case "user-asc":
					$orderBySQL = "userlink ASC";
					break;
				case "user-desc":
					$orderBySQL = "userlink DESC";
					break;
			}

			$UserInfos = $newStaticBdd->select("id, userlink, fb_firstname, fb_lastname, fb_picture, user_mutant_namecode, fame_level, center_level, time_update", "users", "WHERE fb_firstname = '".$searchContent."' OR fb_lastname = '".$searchContent."' OR userlink = '".$searchContent."' ORDER BY ".$orderBySQL." LIMIT ".$limitNumberStart.", ".$limitNumber."");
			$isUserExist = $newStaticBdd->num_rows($UserInfos);

			if($isUserExist == 1)
			{
				$getUserInfos = $newStaticBdd->fetch_array($UserInfos);
				$mutantDNA = Tool::getSpecimenDNA($getUserInfos['user_mutant_namecode']);

				$mutantIconDNA_0 = "";
				if(!empty($mutantDNA[0]))
				{
					$mutantIconDNA_0 = Tool::getIconDNA($mutantDNA[0]);
				}

				$mutantIconDNA_1 = "";
				if(!empty($mutantDNA[1]))
				{
					$mutantIconDNA_1 = Tool::getIconDNA($mutantDNA[1]);
				}

				self::$dataArray["result"] = true;
				self::$dataArray['error'] = null;
				ob_start();
				include('../models/user_card.php');
				self::$dataArray['reply'] .= ob_get_contents();
				ob_end_clean();
			}
			else
			{
				self::$dataArray["result"] = false;
				self::$dataArray['error'] = "No user found ...";
				self::$dataArray['reply'] = null;
			}
		}
		else
		{
			if(isset($sortByValue) AND !empty($sortByValue))
			{
				$sortByValue = $newStaticBdd->real_escape_string(htmlspecialchars($sortByValue));

				switch ($sortByValue) {
					case "fame-asc":
						$orderBySQL = "fame_level ASC";
						break;
					case "fame-desc":
						$orderBySQL = "fame_level DESC";
						break;

					case "center-asc":
						$orderBySQL = "center_level ASC";
						break;
					case "center-desc":
						$orderBySQL = "center_level DESC";
						break;

					case "mutant-asc":
						$orderBySQL = "user_mutant_namecode ASC";
						break;
					case "mutant-desc":
						$orderBySQL = "user_mutant_namecode DESC";
						break;

					case "user-asc":
						$orderBySQL = "userlink ASC";
						break;
					case "user-desc":
						$orderBySQL = "userlink DESC";
						break;
				}

				$UserInfos = $newStaticBdd->select("id, userlink, fb_firstname, fb_lastname, fb_picture, fame_level, center_level, user_mutant_namecode, time_update", "users", "ORDER BY ".$orderBySQL." LIMIT ".$limitNumberStart.", ".$limitNumber."");
				while($getUserInfos = $newStaticBdd->fetch_array($UserInfos))
				{
					$mutantDNA = Tool::getSpecimenDNA($getUserInfos['user_mutant_namecode']);

					$mutantIconDNA_0 = "";
					if(!empty($mutantDNA[0]))
					{
						$mutantIconDNA_0 = Tool::getIconDNA($mutantDNA[0]);
					}

					$mutantIconDNA_1 = "";
					if(!empty($mutantDNA[1]))
					{
						$mutantIconDNA_1 = Tool::getIconDNA($mutantDNA[1]);
					}

					ob_start();
					include('../models/user_card.php');
					self::$dataArray['reply'] .= ob_get_contents();
					ob_end_clean();
				}

				self::$dataArray["result"] = true;
				self::$dataArray['error'] = null;
			}
			else
			{
				self::$dataArray["reply"] = null;
				self::$dataArray["result"] = false;
				self::$dataArray['error'] = "sortByValue not set !";
			}
		}

		return self::$dataArray;
	}

	public static function getReleaseDate($releaseName)
	{
		$newStaticBdd = new BDD();
		$dataArray = array();

		if(!empty($releaseName))
		{
			$releaseName = $newStaticBdd->real_escape_string(htmlspecialchars($releaseName));

			$releaseInfos = $newStaticBdd->select("*", "release_date", "WHERE name LIKE '".$releaseName."'");
			$isReleaseExist = $newStaticBdd->num_rows($releaseInfos);

			if($isReleaseExist == 1)
			{
				$getReleaseInfos = $newStaticBdd->fetch_array($releaseInfos);

				if($getReleaseInfos['activated'] == 1)
				{
					$releaseDate = strtotime($getReleaseInfos['date']);
					$timeRemaining = $releaseDate - time();

					if($timeRemaining > 0)
					{
						$days = floor($timeRemaining/(60*60*24));
						if($days < 10)
						{
							$days = '0'.$days;
						}
						$hours = round(($timeRemaining-$days*60*60*24)/(60*60));
						if($hours < 10)
						{
							$hours = '0'.$hours;
						}
						$timeRemainingResult = $days.':'.$hours.date(':i:s', $timeRemaining);

						$dataArray["result"] = true;
						$dataArray['error'] = null;
						$dataArray['reply'] = $timeRemainingResult;
					}
					else
					{
						$dataArray["result"] = false;
						$dataArray['error'] = "Releasedate off";
						$dataArray['reply'] = "OVER !";
					}
				}
				else
				{
					$dataArray["result"] = false;
					$dataArray['error'] = "This release is not activated !";
					$dataArray['reply'] = null;
				}
			}
			else
			{
				$dataArray["result"] = false;
				$dataArray['error'] = "No release with name ".$releaseName." found !";
				$dataArray['reply'] = null;
			}
		}
		else
		{
			$dataArray["result"] = false;
			$dataArray['error'] = "releaseName is empty !";
			$dataArray['reply'] = null;
		}

		return $dataArray;
	}

	public static function getReports() {
		$dataArray = array();

		if(User::isAdmin()) {
			$newStaticBdd = new BDD();

			$ReportsInfos = $newStaticBdd->select("id, reporting_from, reported_player, report_message, date", "report_player", "ORDER BY date DESC");
			while($getReportsInfos = $newStaticBdd->fetch_array($ReportsInfos)) {
				ob_start();
				include('../models/report-container.php');
				$dataArray['reply'] .= ob_get_contents();
				ob_end_clean();
			}

			return $dataArray;
		}
	}

	public static function getUserInfos() {
		$dataArray = array();

		if(User::isAdmin()) {
			$newStaticBdd = new BDD();

			$UserInfos = $newStaticBdd->select("id, fb_firstname, fb_lastname, fb_picture, email, user_ip", "users", "ORDER BY id ASC");
			while($getUserInfos = $newStaticBdd->fetch_array($UserInfos)) {
				ob_start();
				include('../models/users-infos-container.php');
				$dataArray['reply'] .= ob_get_contents();
				ob_end_clean();
			}

			return $dataArray;
		}
	}
}

?>
