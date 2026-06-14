<?php

namespace App\Controllers;

use App\Auth;
use App\CGUtils;
use App\CoreUtils;
use App\Cutiemarks;
use App\DB;
use App\Input;
use App\JSON;
use App\Logs;
use App\Models\Appearance;
use App\Models\Color;
use App\Models\ColorGroup;
use App\Models\MajorChange;
use App\Permission;
use App\Regexes;
use App\Response;
use OpenApi\Annotations as OA;
use function count;

/**
 * @OA\Schema(
 *   schema="PrivateColor",
 *   type="object",
 *   description="Represents a single color within a color group",
 *   additionalProperties=false,
 *   @OA\Property(property="id", ref="#/components/schemas/OneBasedId"),
 *   @OA\Property(property="order", type="integer", minimum=1),
 *   @OA\Property(property="label", type="string", minLength=3, maxLength=30),
 *   @OA\Property(property="hex", type="string", nullable=true, description="Hex color code, e.g. #FF0000")
 * )
 * @OA\Schema(
 *   schema="PrivateColorGroup",
 *   type="object",
 *   description="Represents a color group belonging to an appearance",
 *   required={
 *     "id",
 *     "appearance_id",
 *     "order",
 *     "label"
 *   },
 *   @OA\Property(property="id", ref="#/components/schemas/OneBasedId"),
 *   @OA\Property(property="appearance_id", ref="#/components/schemas/OneBasedId"),
 *   @OA\Property(property="order", type="integer", minimum=1),
 *   @OA\Property(property="label", type="string", minLength=2, maxLength=30),
 *   @OA\Property(property="Colors", type="array", description="Only present in the GET response", @OA\Items(ref="#/components/schemas/PrivateColor"))
 * )
 */
class ColorGroupController extends ColorGuideController {
  /** @var ColorGroup|null */
  private $colorgroup;

  private function load_colorgroup($params) {
    $this->_initialize($params);
    if (!Auth::$signed_in)
      Response::fail();

    if (!$this->creating){
      if (empty($params['id']))
        Response::fail('Missing color group ID');
      $groupID = (int)$params['id'];
      $this->colorgroup = ColorGroup::find($groupID);
      if (empty($this->colorgroup))
        Response::fail("There's no color group with the ID of $groupID");
      if (($this->colorgroup->appearance->owner_id === null || $this->colorgroup->appearance->owner_id !== Auth::$user->id) && Permission::insufficient('staff'))
        Response::fail();
    }
  }

  /**
   * @OA\Get(
   *   path="/cg/colorgroup/{id}",
   *   description="Get a color group's details and its colors. The user must be signed in and either own the appearance's personal guide or be staff.",
   *   tags={"color groups"},
   *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(ref="#/components/schemas/OneBasedId")),
   *   @OA\Response(
   *     response="200",
   *     description="OK",
   *     @OA\JsonContent(allOf={
   *       @OA\Schema(ref="#/components/schemas/ServerResponse"),
   *       @OA\Schema(ref="#/components/schemas/PrivateColorGroup")
   *     })
   *   ),
   *   @OA\Response(response="401", description="Not signed in", @OA\JsonContent(ref="#/components/schemas/ServerResponse")),
   *   @OA\Response(response="400", description="Missing/invalid color group ID, or insufficient permission", @OA\JsonContent(ref="#/components/schemas/ServerResponse"))
   * )
   * @OA\Post(
   *   path="/cg/colorgroup",
   *   description="Create a new color group with its colors on an appearance. The user must be signed in and have permission to manage the appearance.",
   *   tags={"color groups"},
   *   @OA\RequestBody(required=true, @OA\JsonContent(
   *     required={"ponyid","label","Colors"},
   *     @OA\Property(property="ponyid", ref="#/components/schemas/OneBasedId", description="ID of the appearance to add the color group to"),
   *     @OA\Property(property="label", type="string", minLength=2, maxLength=30, description="Color group label"),
   *     @OA\Property(property="Colors", type="array", minItems=1, description="Colors belonging to this group", @OA\Items(
   *       required={"label"},
   *       @OA\Property(property="label", type="string", minLength=3, maxLength=30),
   *       @OA\Property(property="hex", type="string", nullable=true, description="Hex color code")
   *     )),
   *     @OA\Property(property="major", type="boolean", description="Whether to record this as a major change (only for non-personal guide appearances)"),
   *     @OA\Property(property="reason", type="string", maxLength=255, description="Reason for the change, required if major is set"),
   *     @OA\Property(property="APPEARANCE_PAGE", type="boolean", description="Whether this request originates from the appearance page; affects which HTML fragments are returned")
   *   )),
   *   @OA\Response(
   *     response="200",
   *     description="OK",
   *     @OA\JsonContent(allOf={
   *       @OA\Schema(ref="#/components/schemas/ServerResponse"),
   *       @OA\Schema(type="object",
   *         @OA\Property(property="cgs", type="string", description="Rendered HTML of the appearance's color groups"),
   *         @OA\Property(property="changes", type="string", description="Rendered HTML of the major changes section, present when major changes apply and called from the appearance page"),
   *         @OA\Property(property="update", type="string", description="Rendered HTML update notice, present when major changes apply and not called from the appearance page"),
   *         @OA\Property(property="cm_list", type="string", description="Rendered HTML of the cutie marks list, present when called from the appearance page"),
   *         @OA\Property(property="notes", type="string", description="Rendered HTML of the appearance's notes, present when not called from the appearance page")
   *       )
   *     })
   *   ),
   *   @OA\Response(response="403", description="Insufficient permission to manage this appearance", @OA\JsonContent(ref="#/components/schemas/ServerResponse")),
   *   @OA\Response(response="400", description="Validation error, e.g. duplicate color group label or invalid color data", @OA\JsonContent(ref="#/components/schemas/ServerResponse"))
   * )
   * @OA\Put(
   *   path="/cg/colorgroup/{id}",
   *   description="Update an existing color group and its colors. The user must be signed in and either own the appearance's personal guide or be staff.",
   *   tags={"color groups"},
   *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(ref="#/components/schemas/OneBasedId")),
   *   @OA\RequestBody(required=true, @OA\JsonContent(
   *     required={"label","Colors"},
   *     @OA\Property(property="label", type="string", minLength=2, maxLength=30, description="Color group label"),
   *     @OA\Property(property="Colors", type="array", minItems=1, description="Colors belonging to this group. Existing colors should include their id; omitted existing colors are deleted", @OA\Items(
   *       required={"label"},
   *       @OA\Property(property="id", ref="#/components/schemas/OneBasedId"),
   *       @OA\Property(property="label", type="string", minLength=3, maxLength=30),
   *       @OA\Property(property="hex", type="string", nullable=true, description="Hex color code")
   *     )),
   *     @OA\Property(property="major", type="boolean", description="Whether to record this as a major change (only for non-personal guide appearances)"),
   *     @OA\Property(property="reason", type="string", maxLength=255, description="Reason for the change, required if major is set"),
   *     @OA\Property(property="APPEARANCE_PAGE", type="boolean", description="Whether this request originates from the appearance page; affects which HTML fragments are returned"),
   *     @OA\Property(property="FULL_CHANGES_SECTION", type="boolean", description="When present and major changes apply on the appearance page, wraps the changes HTML in the full changes section")
   *   )),
   *   @OA\Response(
   *     response="200",
   *     description="OK",
   *     @OA\JsonContent(allOf={
   *       @OA\Schema(ref="#/components/schemas/ServerResponse"),
   *       @OA\Schema(type="object",
   *         @OA\Property(property="cgs", type="string", description="Rendered HTML of the appearance's color groups"),
   *         @OA\Property(property="changes", type="string", description="Rendered HTML of the major changes section, present when major changes apply and called from the appearance page"),
   *         @OA\Property(property="update", type="string", description="Rendered HTML update notice, present when major changes apply and not called from the appearance page"),
   *         @OA\Property(property="cm_list", type="string", description="Rendered HTML of the cutie marks list, present when called from the appearance page"),
   *         @OA\Property(property="notes", type="string", description="Rendered HTML of the appearance's notes, present when not called from the appearance page")
   *       )
   *     })
   *   ),
   *   @OA\Response(response="400", description="Color group not found, insufficient permission, or validation error", @OA\JsonContent(ref="#/components/schemas/ServerResponse"))
   * )
   * @OA\Delete(
   *   path="/cg/colorgroup/{id}",
   *   description="Delete a color group and its colors. The user must be signed in and either own the appearance's personal guide or be staff.",
   *   tags={"color groups"},
   *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(ref="#/components/schemas/OneBasedId")),
   *   @OA\Response(response="200", description="OK", @OA\JsonContent(ref="#/components/schemas/ServerResponse")),
   *   @OA\Response(response="401", description="Not signed in", @OA\JsonContent(ref="#/components/schemas/ServerResponse")),
   *   @OA\Response(response="400", description="Color group not found or insufficient permission", @OA\JsonContent(ref="#/components/schemas/ServerResponse"))
   * )
   */
  public function api($params) {
    $this->load_colorgroup($params);

    switch ($this->action){
      case 'GET':
        $out = $this->colorgroup->to_array();
        $out['Colors'] = [];
        foreach ($this->colorgroup->colors as $c){
          /** @noinspection UnsupportedStringOffsetOperationsInspection */
          $out['Colors'][] = $c->to_array([
            'except' => 'group_id',
          ]);
        }
        Response::done($out);
      break;
      case 'POST':
      case 'PUT':
        if ($this->creating){
          $ponyid = (new Input('ponyid', 'int', [
            Input::CUSTOM_ERROR_MESSAGES => [
              Input::ERROR_MISSING => 'Appearance ID is missing',
              Input::ERROR_INVALID => 'Appearance ID is invalid',
            ],
          ]))->out();
          $params['id'] = $ponyid;
          $this->load_appearance($params);
          $this->appearance->enforceManagePermission();
          $this->colorgroup = new ColorGroup();
          $this->colorgroup->appearance_id = $ponyid;
        }

        if (!$this->creating)
          $oldlabel = $this->colorgroup->label;
        $label = (new Input('label', 'string', [
          Input::IN_RANGE => [2, 30],
          Input::CUSTOM_ERROR_MESSAGES => [
            Input::ERROR_MISSING => 'Color group label is missing',
            Input::ERROR_RANGE => 'Color group label must be between @min and @max characters long',
          ],
        ]))->out();
        CoreUtils::checkStringValidity($label, 'Color group label', INVERSE_PRINTABLE_ASCII_PATTERN, true);
        if (!$this->creating)
          DB::$instance->where('id', $this->colorgroup->id, '!=');
        if (DB::$instance->where('appearance_id', $this->colorgroup->appearance_id)->where('label', $label)->has(ColorGroup::$table_name))
          Response::fail('There is already a color group with the same name on this appearance.');
        $this->colorgroup->label = $label;

        if ($this->colorgroup->appearance->owner_id === null){
          $major = isset($_REQUEST['major']);
          if ($major){
            $reason = (new Input('reason', 'string', [
              Input::IN_RANGE => [null, 255],
              Input::CUSTOM_ERROR_MESSAGES => [
                Input::ERROR_MISSING => 'Please specify a reason for the changes',
                Input::ERROR_RANGE => 'The reason cannot be longer than @max characters',
              ],
            ]))->out();
            CoreUtils::checkStringValidity($reason, 'Change reason');
          }
        }

        $this->colorgroup->save();

        $oldcolors = $this->creating ? null : $this->colorgroup->colors;
        $oldColorIDs = [];
        if (!$this->creating){
          foreach ($oldcolors as $oc)
            $oldColorIDs[] = $oc->id;
        }

        /** @var $recvColors array */
        $recvColors = (new Input('Colors', 'json', [
          Input::CUSTOM_ERROR_MESSAGES => [
            Input::ERROR_MISSING => 'Missing list of colors',
            Input::ERROR_INVALID => 'List of colors is invalid',
          ],
        ]))->out();
        if (count($recvColors) < 1)
          Response::fail('Each color group must have at least one color');

        /** @var $newcolors Color[] */
        $newcolors = [];
        /** @var $recvColorIDs int[] */
        $recvColorIDs = [];
        /** @var $check_colors_of Appearance[] */
        $check_colors_of = [];
        foreach ($recvColors as $part => $c){
          if (!empty($c['id'])){
            $append = Color::find($c['id']);
            if (empty($append))
              Response::fail("Trying to edit color with ID {$c['id']} which does not exist");
            if ($append->group_id !== $this->colorgroup->id)
              Response::fail("Trying to modify color with ID {$c['id']} which is not part of the color group you're editing");
            $append->order = $part + 1;
            $index = "(ID: {$c['id']})";
            $recvColorIDs[] = $c['id'];
          }
          else {
            $append = new Color([
              'group_id' => $this->colorgroup->id,
              'order' => $part + 1,
            ]);
            $index = "(index: $part)";
          }

          if (empty($c['label']))
            Response::fail("You must specify a color name $index");
          $label = CoreUtils::trim($c['label']);
          CoreUtils::checkStringValidity($label, "Color $index name");
          $ll = mb_strlen($label);
          if ($ll < 3 || $ll > 30)
            Response::fail("The color name must be between 3 and 30 characters in length $index");
          $append->label = $label;

          if (!empty($c['hex'])) {
            $hex = CoreUtils::trim($c['hex']);
            if (!Regexes::$hex_color->match($hex, $_match))
              Response::fail('Hex color '.CoreUtils::escapeHTML($hex)." is invalid, please leave empty or fix $index");
            $append->hex = '#'.strtoupper($_match[1]);
            if ($this->colorgroup->appearance->owner_id === null)
              $append->hex = CGUtils::roundHex($append->hex);
          }

          $newcolors[] = $append;
        }
        if (!$this->creating){
          /** @var $removedColorIDs int[] */
          $removedColorIDs = CoreUtils::array_subtract($oldColorIDs, $recvColorIDs);
          $removedColors = [];
          if (!empty($removedColorIDs)){
            /** @var $removedColors Color[] */
            $removedColors = DB::$instance->where('id', $removedColorIDs)->get('colors');
          }
        }
        $newlabels = [];
        foreach ($newcolors as $color){
          if (isset($newlabels[$color->label]))
            Response::fail('The color name "'.CoreUtils::escapeHTML($color->label).'" appears in this color group more than once. Please choose a unique name or add numbering to the colors.');

          $newlabels[$color->label] = true;
        }
        unset($newlabels);
        #### Validation ends here - No removal/modification of any colors before this point ####

        $colorError = false;
        foreach ($newcolors as $c){
          if ($c->save())
            continue;

          $colorError = true;
          CoreUtils::logError(__METHOD__.': Database error triggered by user '.Auth::$user->name.' ('.Auth::$user->id.") while saving colors:\n".JSON::encode($c->errors, JSON_PRETTY_PRINT));
        }
        if (!$this->creating && !empty($removedColors)){
          foreach ($removedColors as $color)
            $color->delete();
        }
        /** @var $newcolors Color[] */
        if ($colorError)
          Response::fail("There were some issues while saving the colors. Please <a class='send-feedback'>let us know</a> about this error, so we can look into why it might've happened.");

        if (!isset($check_colors_of[$this->colorgroup->appearance_id]))
          $check_colors_of[$this->colorgroup->appearance_id] = $this->colorgroup->appearance;
        $isCMGroup = $this->colorgroup->label === 'Cutie Mark';
        foreach ($check_colors_of as $appearance){
          $appearance->clearRenderedImages([Appearance::CLEAR_CMDIR, Appearance::CLEAR_PALETTE, Appearance::CLEAR_PREVIEW]);
          if ($isCMGroup)
            $appearance->clearRenderedImages([Appearance::CLEAR_CM]);
        }

        $response = ['cgs' => $this->colorgroup->appearance->getColorsHTML(compact: !$this->appearance_page, wrap: NOWRAP)];

        if ($this->colorgroup->appearance->owner_id === null && $major){
          MajorChange::record($this->colorgroup->appearance_id, $reason);
          if ($this->appearance_page){
            $FullChangesSection = isset($_REQUEST['FULL_CHANGES_SECTION']);
            $response['changes'] = CGUtils::getMajorChangesHTML(MajorChange::get($this->colorgroup->appearance_id, null), $FullChangesSection);
            if ($FullChangesSection)
              $response['changes'] = str_replace('@', $response['changes'], CGUtils::CHANGES_SECTION);
          }
          else $response['update'] = $this->colorgroup->appearance->getUpdatesHTML();
        }

        if ($this->appearance_page)
          $response['cm_list'] = Cutiemarks::getListForAppearancePage(CutieMarks::get($this->colorgroup->appearance), NOWRAP);
        else $response['notes'] = Appearance::find($this->colorgroup->appearance_id)->getNotesHTML(NOWRAP);

        $logdata = [];
        if ($this->creating) Logs::logAction('cgs', [
          'action' => 'add',
          'group_id' => $this->colorgroup->id,
          'appearance_id' => $this->colorgroup->appearance_id,
          'label' => $this->colorgroup->label,
          'order' => $this->colorgroup->order,
        ]);
        else if ($this->colorgroup->label !== $oldlabel){
          $logdata['oldlabel'] = $oldlabel;
          $logdata['newlabel'] = $this->colorgroup->label;
        }

        $oldcolorstr = CGUtils::stringifyColors($oldcolors);
        $newcolorstr = CGUtils::stringifyColors($newcolors);
        $colorsChanged = $oldcolorstr !== $newcolorstr;
        if ($colorsChanged){
          $logdata['oldcolors'] = $oldcolorstr;
          $logdata['newcolors'] = $newcolorstr;
        }
        if (!empty($logdata)){
          $logdata['group_id'] = $this->colorgroup->id;
          $logdata['appearance_id'] = $this->colorgroup->appearance_id;
          Logs::logAction('cg_modify', $logdata);
        }

        Response::done($response);
      break;
      case 'DELETE':
        $Appearance = $this->colorgroup->appearance;

        $this->colorgroup->delete();

        Logs::logAction('cgs', [
          'action' => 'del',
          'group_id' => $this->colorgroup->id,
          'appearance_id' => $this->colorgroup->appearance_id,
          'label' => $this->colorgroup->label,
          'order' => $this->colorgroup->order,
        ]);

        Response::success('Color group deleted successfully');
      break;
      default:
        CoreUtils::notAllowed();
    }
  }
}
