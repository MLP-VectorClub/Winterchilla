<?php

namespace App;

use App\Models\Tag;

class Tags {
	// List of available tag types
	public const TAG_TYPES = [
		'app' => 'Clothing',
		'cat' => 'Category',
		'ep' => 'Episode',
		'gen' => 'Gender',
		'spec' => 'Species',
		'char' => 'Character',
	];

	/**
	 * Retrieve set of tags for a given appearance
	 *
	 * @param int       $PonyID
	 * @param array|int $limit
	 * @param bool      $showEpTags
	 * @param bool      $exporting
	 *
	 * @return Tag[]
	 */
	public static function getFor($PonyID = null, $limit = null, $showEpTags = false, $exporting = false){
		if (!$exporting){
			if ($PonyID !== null)
				DB::$instance->where('"synonym_of" IS NULL');
			DB::$instance
				->orderByLiteral('CASE WHEN tags.type IS NULL THEN 1 ELSE 0 END')
				->orderBy('tags.type')
				->orderBy('tags.name');
			if (!$showEpTags)
				DB::$instance->where("tags.type != 'ep'");
		}
		else {
			DB::$instance->orderBy('tags.id');
		}
		if ($PonyID !== null){
			DB::$instance->join('tagged','tagged.tag_id = tags.id','right',false);
			DB::$instance->where('tagged.appearance_id',$PonyID);
		}
		return DB::$instance->setModel(Tag::class)->get('tags',$limit,'tags.*');
	}

	/**
	 * Gets a specifig tag while resolving synonym relations
	 *
	 * @param mixed  $value
	 * @param string $column
	 * @param bool   $as_bool Return a boolean reflecting existence
	 *
	 * @return Tag|bool
	 */
	public static function getActual($value, $column = 'id', $as_bool = false){
		$arg1 = $as_bool === RETURN_AS_BOOL ? 'synonym_of,id' : '*';

		/** @var $Tag Tag */
		$Tag = DB::$instance->where($column, $value)->getOne('tags', $arg1);

		if ($Tag !== null && $Tag->synonym_of !== null)
			$Tag = $Tag->synonym;

		return $as_bool === RETURN_AS_BOOL ? !empty($Tag) : $Tag;
	}

	/**
	 * Gets the tag which the specified tag is a synonym of
	 *
	 * @deprecated
	 *
	 * @param Tag $Tag
	 *
	 * @return Tag
	 */
	public static function getSynonymOf(Tag $Tag){
		return $Tag->synonym;
	}

	/**
	 * Update use count on a tag
	 *
	 * @param int  $TagID
	 * @param bool $returnCount
	 *
	 * @return array
	 */
	public static function updateUses(int $TagID, bool $returnCount = false):array {
		$Tagged = DB::$instance->where('tag_id', $TagID)->count('tagged');
		$return = ['status' => DB::$instance->where('id', $TagID)->update('tags', ['uses' => $Tagged])];

		if ($returnCount)
			$return['count'] = $Tagged;
		return $return;
	}

	/**
	 * Generates the markup for the tags sub-page
	 *
	 * @param Tag[] $Tags
	 * @param bool  $wrap
	 *
	 * @return string
	 */
	public static function getTagListHTML(array $Tags, $wrap = WRAP){
		$HTML =
		$utils =
		$refresh = '';

		$canEdit = Permission::sufficient('staff');
		if ($canEdit){
			$refresh = " <button class='typcn typcn-arrow-sync refresh' title='Refresh use count'></button>";
			$utils = "<td class='utils align-center'><button class='typcn typcn-trash delete' title='Delete'></button> <button class='typcn typcn-flow-children synon' title='Make synonym'></button></td>";
		}

		if (!empty($Tags)) foreach ($Tags as $t){
			$trClass = $t->type ? " class='typ-{$t->type}'" : '';
			$type = $t->type ? self::TAG_TYPES[$t->type] : '';
			$search = CoreUtils::aposEncode(urlencode($t->name));
			$titleName = CoreUtils::aposEncode($t->name);
			$name = CoreUtils::escapeHTML($t->name);

			$title = $t->synonym_of !== null
				? (
					empty($t->title)
					? ''
					: $t->title.'<br>'
				)."<em>Synonym of <strong>{$t->synonym->name}</strong></em>"
				: $t->title;

			$localRefresh = $t->synonym_of === null ? $refresh : '';

			$HTML .= <<<HTML
			<tr $trClass>
				<td class="tid">{$t->id}</td>
				<td class="name"><a href='/cg?q=$search' title='Search for $titleName'><span class="typcn typcn-zoom"></span>$name</a></td>$utils
				<td class="title">$title</td>
				<td class="type">$type</td>
				<td class="uses"><span>{$t->uses}</span>$localRefresh</td>
			</tr>
HTML;
		}

		return $wrap ? "<tbody>$HTML</tbody>" : $HTML;
	}
}