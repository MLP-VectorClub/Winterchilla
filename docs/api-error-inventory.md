# API error inventory (generated, heuristic)

Generated from every `Response::fail` / `failApi` / `dbError` / `success` call in `app/`. The *suggested status* is a
keyword heuristic on the message and must be reviewed per call before it is applied. `401/403` = empty message, which
today resolves to 401-or-403 depending on whether the visitor is signed in.

Totals (non-success): 400: 75, 401: 2, 401/403: 41, 403: 18, 404: 24, 409: 34, 422: 54, 500: 31, 503: 8. `success` calls: 26.


## app/Appearances.php

| Line | Function | Kind | Message | Suggested |
|---|---|---|---|---|
| 116 | reorder | fail | `"Updating appearance #{$app->id} failed` | 400 |
| 139 | reindex | fail | `'Re-index failed` | 400 |
| 188 | reindex | fail | `'Failed to create index:<br><pre>'.CoreUtils::escapeHTML(JSON::encode(JSON::decode($e->...` | 400 |
| 191 | reindex | fail | `'Re-index failed` | 400 |
| 219 | reindex | success | `'Re-index completed'` | 200 |
| 232 | handleBulkError | fail | `'Bulk index update failed` | 400 |

## app/CGUtils.php

| Line | Function | Kind | Message | Suggested |
|---|---|---|---|---|
| 154 | getFullListHTML | fail | `"Unknown full list sorting order: $order_by"` | 404 |
| 244 | processUploadedImage | fail | `'File upload failed; Reason unknown'` | 404 |
| 252 | processUploadedImage | fail | `'File upload failed; Writing image file was unsuccessful'` | 400 |
| 269 | grabImage | fail | `$e->getMessage(` | 400 |
| 273 | grabImage | fail | `'Image could not be retrieved from external provider'` | 400 |
| 277 | grabImage | fail | `'Remote file could not be found'` | 404 |
| 279 | grabImage | fail | `'Writing local image file was unsuccessful'` | 400 |
| 564 | renderAppearancePNG | fail | `'Failed to create render directory'` | 400 |
| 574 | renderCMFacingSVG | fail | `'Invalid facing value specified!'` | 422 |
| 631 | getSpriteImageMap | fail | `"There's no sprite image for appearance #$AppearanceID"` | 400 |

## app/Controllers/API/AboutAPIController.php

| Line | Function | Kind | Message | Suggested |
|---|---|---|---|---|
| 82 | server | fail | `'GIT_INFO_MISSING'` | 422 |

## app/Controllers/API/AdminAPIController.php

| Line | Function | Kind | Message | Suggested |
|---|---|---|---|---|
| 57 | logDetail | fail | `'Entry ID is missing or invalid'` | 422 |
| 62 | logDetail | fail | `'Log entry does not exist'` | 404 |
| 64 | logDetail | fail | `'There are no details to show'` | 400 |
| 80 | load_useful_link | fail | `'The specified link does not exist'` | 404 |
| 161 | usefulLinksApi | dbError | `` | 500 |
| 207 | usefulLinksApi | fail | `` | 401/403 |
| 218 | usefulLinksApi | fail | `'Nothing was changed'` | 400 |
| 223 | usefulLinksApi | dbError | `` | 500 |
| 263 | reorderUsefulLinks | fail | `"Updating link #$id failed` | 400 |
| 277 | load_notice | fail | `'The specified notice does not exist'` | 404 |

## app/Controllers/API/AppearanceAPIController.php

| Line | Function | Kind | Message | Suggested |
|---|---|---|---|---|
| 153 | api | fail | `` | 401/403 |
| 208 | api | fail | `'You already have an appearance with the same name in your Personal Color Guide'` | 409 |
| 210 | api | fail | `"An appearance <a href='{$dupe->toURL()}' target='_blank'>already exists</a> in the ".C...` | 409 |
| 334 | api | fail | `'This appearance cannot be deleted because it\'s currently pinned'` | 409 |
| 339 | api | dbError | `` | 500 |
| 365 | api | success | `'Appearance removed'` | 200 |
| 404 | applyTemplate | fail | `'Applying the template failed. Reason: '.$e->getMessage(` | 400 |
| 477 | selectiveClear | dbError | `` | 500 |
| 484 | selectiveClear | dbError | `` | 500 |
| 491 | selectiveClear | dbError | `` | 500 |
| 502 | selectiveClear | dbError | `'Failed to wipe tags'` | 500 |
| 604 | colorGroupsApi | fail | `'This appearance does not have any color groups'` | 400 |
| 606 | colorGroupsApi | fail | `'An appearance needs at least 2 color groups before you can change their order'` | 400 |
| 627 | colorGroupsApi | fail | `"There's no group with the ID of $GroupID on this appearance"` | 400 |
| 704 | spriteApi | fail | `'You are not allowed to upload sprite images on your own PCG appearances'` | 403 |
| 713 | spriteApi | fail | `'No sprite file found'` | 400 |
| 782 | relationsApi | fail | `'Relations are unavailable for appearances in personal guides'` | 503 |
| 944 | cutiemarkApi | fail | `'Appearances can only have a maximum of 4 cutie marks.'` | 422 |
| 954 | cutiemarkApi | fail | `"The cutie mark you're trying to update (#{$item['id']}) does not exist"` | 404 |
| 964 | cutiemarkApi | fail | `'SVG data is missing'` | 422 |
| 966 | cutiemarkApi | fail | `'SVG data exceeds the maximum size of 1 MB'` | 400 |
| 968 | cutiemarkApi | fail | `'SVG data is invalid'` | 422 |
| 979 | cutiemarkApi | fail | `'Cutie mark label must be between 1 and 32 chars long'` | 422 |
| 981 | cutiemarkApi | fail | `'Cutie mark labels must be unique within an appearance'` | 422 |
| 993 | cutiemarkApi | fail | `'Body orientation "'.CoreUtils::escapeHTML($facing).'" is invalid'` | 422 |
| 1001 | cutiemarkApi | fail | `'Deviation link is missing'` | 422 |
| 1009 | cutiemarkApi | fail | `'The link must point to a DeviantArt submission` | 422 |
| 1012 | cutiemarkApi | fail | `'Error while checking deviation link: '.$e->getMessage(` | 422 |
| 1016 | cutiemarkApi | fail | `'The provided deviation could not be fetched'` | 503 |
| 1020 | cutiemarkApi | fail | `"The provided deviation's creator could not be fetched"` | 503 |
| 1025 | cutiemarkApi | fail | `'Username is missing'` | 422 |
| 1027 | cutiemarkApi | fail | `"Username ({$item['username']}) is invalid"` | 422 |
| 1030 | cutiemarkApi | fail | `"The provided deviation's creator could not be fetched"` | 503 |
| 1039 | cutiemarkApi | fail | `'The specified attribution method is invalid'` | 422 |
| 1043 | cutiemarkApi | fail | `'Preview rotation amount is missing'` | 422 |
| 1045 | cutiemarkApi | fail | `'Preview rotation must be a number'` | 422 |
| 1048 | cutiemarkApi | fail | `'Preview rotation must be between -45 and 45'` | 422 |
| 1059 | cutiemarkApi | dbError | `"Saving cutie mark (index $i) failed"` | 500 |
| 1068 | cutiemarkApi | fail | `"Saving SVG data for cutie mark (index $i) failed"` | 400 |
| 1153 | taggedApi | fail | `'Tagging is unavailable for appearances in personal guides'` | 503 |
| 1156 | taggedApi | fail | `'This appearance cannot be tagged'` | 409 |
| 1220 | sanitizeSvg | fail | `` | 401/403 |
| 1294 | guideRelationsApi | fail | `` | 401/403 |
| 1381 | pinApi | fail | `` | 401/403 |
| 1386 | pinApi | fail | `'Appearances in personal guides cannot be pinned'` | 409 |
| 1392 | pinApi | success | `'This appearance is already pinned'` | 200 |
| 1401 | pinApi | success | `'The appearance has been pinned successfully'` | 200 |
| 1407 | pinApi | success | `'This appearance was not pinned before'` | 200 |
| 1412 | pinApi | success | `'The appearance has been unpinned successfully'` | 200 |

## app/Controllers/API/AppearancesAPIController.php

| Line | Function | Kind | Message | Suggested |
|---|---|---|---|---|
| 444 | queryPublic | fail | `'ELASTIC_DOWN'` | 503 |
| 455 | queryPublic | fail | `'COLOR_GUIDE.INVALID_GUIDE_NAME'` | 422 |
| 529 | queryAll | fail | `'COLOR_GUIDE.INVALID_GUIDE_NAME'` | 422 |
| 554 | _resolveAppearance | fail | `'COLOR_GUIDE.APPEARANCE_NOT_FOUND'` | 400 |
| 567 | _handlePrivateAppearanceCheck | fail | `'COLOR_GUIDE.APPEARANCE_PRIVATE'` | 400 |

## app/Controllers/API/AuthAPIController.php

| Line | Function | Kind | Message | Suggested |
|---|---|---|---|---|
| 57 | signOut | success | `"You're not signed in"` | 200 |
| 65 | signOut | fail | `` | 401/403 |
| 68 | signOut | fail | `"Target user doesn't exist"` | 404 |
| 80 | signOut | fail | `'Could not remove information from database'` | 422 |

## app/Controllers/API/ColorGroupAPIController.php

| Line | Function | Kind | Message | Suggested |
|---|---|---|---|---|
| 67 | load_colorgroup | fail | `` | 401/403 |
| 71 | load_colorgroup | fail | `'Missing color group ID'` | 422 |
| 75 | load_colorgroup | fail | `"There's no color group with the ID of $groupID"` | 400 |
| 77 | load_colorgroup | fail | `` | 401/403 |
| 221 | api | fail | `'There is already a color group with the same name on this appearance.'` | 409 |
| 255 | api | fail | `'Each color group must have at least one color'` | 422 |
| 267 | api | fail | `"Trying to edit color with ID {$c['id']} which does not exist"` | 404 |
| 269 | api | fail | `"Trying to modify color with ID {$c['id']} which is not part of the color group you're ...` | 400 |
| 283 | api | fail | `"You must specify a color name $index"` | 422 |
| 288 | api | fail | `"The color name must be between 3 and 30 characters in length $index"` | 422 |
| 294 | api | fail | `'Hex color '.CoreUtils::escapeHTML($hex)." is invalid` | 422 |
| 314 | api | fail | `'The color name "'.CoreUtils::escapeHTML($color->label).'" appears in this color group ...` | 400 |
| 335 | api | fail | `"There were some issues while saving the colors. Please <a class='send-feedback'>let us...` | 422 |
| 404 | api | success | `'Color group deleted successfully'` | 200 |

## app/Controllers/API/ColorGuideAPIController.php

| Line | Function | Kind | Message | Suggested |
|---|---|---|---|---|
| 48 | reorderFullList | fail | `` | 401/403 |
| 101 | reindex | fail | `` | 401/403 |

## app/Controllers/API/EventAPIController.php

| Line | Function | Kind | Message | Suggested |
|---|---|---|---|---|
| 93 | api | fail | `` | 401/403 |
| 97 | api | fail | `'Fetching event details is currently not allowed.'` | 403 |
| 101 | api | fail | `($this->creating ? 'Creating new' : 'Editing existing').' events is currently not allow...` | 403 |
| 104 | api | fail | `'Deleting events is currently not allowed.'` | 403 |
| 124 | finalize | fail | `` | 401/403 |
| 126 | finalize | fail | `"Events can't be finalized currently."` | 409 |
| 144 | checkEntries | fail | `` | 401/403 |
| 146 | checkEntries | fail | `"Events can't receive entries currently."` | 400 |

## app/Controllers/API/EventEntryAPIController.php

| Line | Function | Kind | Message | Suggested |
|---|---|---|---|---|
| 41 | load_event_entry | fail | `` | 401/403 |
| 44 | load_event_entry | fail | `'Entry ID is missing or invalid'` | 422 |
| 48 | load_event_entry | fail | `'The requested entry could not be found'` | 404 |
| 53 | load_event_entry | fail | `"You don't have permission to manage this entry"` | 403 |
| 58 | load_event_entry | fail | `'This event has ended` | 400 |
| 78 | _processEntryData | fail | `'Entry link must point to a deviation or Sta.sh submission'` | 422 |
| 81 | _processEntryData | fail | `'Erroe while checking submission link: '.$e->getMessage(` | 400 |
| 107 | _processEntryData | fail | `'Preview image error: '.$e->getMessage(` | 422 |
| 281 | api | dbError | `'Failed to delete entry'` | 500 |

## app/Controllers/API/NotificationAPIController.php

| Line | Function | Kind | Message | Suggested |
|---|---|---|---|---|
| 18 | __construct | fail | `` | 401/403 |
| 51 | get | fail | `'An error prevented the notifications from appearing. If this persists` | 422 |
| 72 | markRead | fail | `"The notification (#$nid) does not exist"` | 404 |

## app/Controllers/API/PersonalGuideAPIController.php

| Line | Function | Kind | Message | Suggested |
|---|---|---|---|---|
| 99 | slotsApi | fail | `Appearances::PCG_APPEARANCE_MAKE_DISABLED` | 400 |
| 114 | slotsApi | fail | `"$You $nave no available slots left$cont to get more` | 400 |
| 204 | pointsApi | fail | `` | 401/403 |
| 221 | pointsApi | fail | `"You have to enter an integer that isn't 0"` | 422 |
| 225 | pointsApi | fail | `'This would cause the users points to go below 10'` | 400 |
| 242 | pointsApi | success | `"You've successfully $given $nPoints $to {$this->user->name}"` | 200 |

## app/Controllers/API/PostAPIController.php

| Line | Function | Kind | Message | Suggested |
|---|---|---|---|---|
| 42 | _authorize | fail | `` | 401/403 |
| 49 | _authorizeMember | fail | `` | 401/403 |
| 161 | _checkPostEditPermission | fail | `` | 401/403 |
| 241 | reservationApi | fail | `'This endpoint only acts on requests'` | 422 |
| 247 | reservationApi | fail | `'You are not allowed to reserve requests'` | 403 |
| 250 | reservationApi | fail | `'Broken posts cannot be reserved. The image must be updated'.(Permission::sufficient('s...` | 403 |
| 266 | reservationApi | fail | `"You've already reserved this request"` | 409 |
| 268 | reservationApi | fail | `'This request has already been reserved by '.$this->post->reserver->toAnchor(` | 409 |
| 280 | reservationApi | dbError | `` | 500 |
| 304 | reservationApi | fail | `` | 401/403 |
| 307 | reservationApi | fail | `'You must unfinish this request before unreserving it.'` | 409 |
| 314 | reservationApi | dbError | `` | 500 |
| 324 | reservationApi | fail | `` | 401/403 |
| 327 | reservationApi | fail | `'You must unfinish this reservation before deleting it.'` | 409 |
| 330 | reservationApi | dbError | `` | 500 |
| 385 | approvalApi | fail | `'This post has not been reserved by anypony yet'` | 409 |
| 388 | approvalApi | fail | `'Only finished posts can be approved'` | 403 |
| 405 | approvalApi | fail | `` | 401/403 |
| 408 | approvalApi | fail | `'This post has not been approved yet'` | 400 |
| 411 | approvalApi | fail | `"<a href='http://fav.me/{$this->post->deviation_id}' target='_blank' rel='noopener'>Thi...` | 400 |
| 557 | api | fail | `"You are not allowed to post {$kind}s"` | 403 |
| 562 | api | fail | `` | 401/403 |
| 569 | api | fail | `'Getting post image failed. If this persists` | 400 |
| 585 | api | fail | `'The specified show entry does not exist'` | 404 |
| 595 | api | fail | `'The user you wanted to post as does not exist'` | 404 |
| 598 | api | fail | `'The user you wanted to post as is not a club member` | 400 |
| 608 | api | dbError | `` | 500 |
| 619 | api | success | `'Nothing was changed'` | 200 |
| 622 | api | dbError | `` | 500 |
| 698 | finishApi | fail | `'This post has not been reserved by anypony yet'` | 409 |
| 701 | finishApi | fail | `` | 401/403 |
| 709 | finishApi | dbError | `` | 500 |
| 733 | finishApi | success | `$message` | 200 |
| 738 | finishApi | fail | `` | 401/403 |
| 743 | finishApi | dbError | `` | 500 |
| 745 | finishApi | success | `'Reservation deleted'` | 200 |
| 748 | finishApi | fail | `'You cannot remove the reservation from this post'` | 400 |
| 756 | finishApi | fail | `'This reservation was added directly and cannot be marked unfinished. To remove it` | 409 |
| 762 | finishApi | dbError | `` | 500 |
| 808 | locate | fail | `"The post you were linked to has either been deleted or didn't exist in the first place...` | 400 |
| 854 | unbreak | fail | `` | 401/403 |
| 862 | unbreak | fail | `"The $key image appears to be unavailable. Please make sure <a href='$link'>this link</...` | 503 |
| 949 | load_post | fail | `"There's no post with the ID $id"` | 400 |
| 952 | load_post | fail | `'This post has been approved and cannot be edited or removed.'` | 409 |
| 978 | deleteRequest | fail | `'Only requests can be deleted using this endpoint'` | 403 |
| 982 | deleteRequest | fail | `` | 401/403 |
| 985 | deleteRequest | fail | `'You cannot delete a request that has already been reserved by a group member'` | 409 |
| 989 | deleteRequest | dbError | `` | 500 |
| 1048 | setImage | fail | `'This post is locked` | 409 |
| 1052 | setImage | fail | `` | 401/403 |
| 1055 | setImage | fail | `'You cannot change the image of a request that has already been reserved.'` | 409 |
| 1067 | setImage | fail | `"<p class='align-center'>The specified image doesn't seem to exist. Please verify that ...` | 422 |
| 1078 | setImage | dbError | `` | 500 |
| 1170 | addReservation | fail | `` | 401/403 |
| 1184 | addReservation | fail | `'The specified show entry does not exist'` | 404 |
| 1191 | addReservation | dbError | `` | 500 |
| 1196 | addReservation | success | `'Reservation added'` | 200 |
| 1235 | suggestRequest | fail | `'You must be signed in to use this feature.'` | 401 |
| 1250 | suggestRequest | fail | `($already_loaded !== null ? "You've gone through all" : 'There are no').' available req...` | 409 |

## app/Controllers/API/PreferenceAPIController.php

| Line | Function | Kind | Message | Suggested |
|---|---|---|---|---|
| 46 | load_preference | fail | `'The specified user does not exist'` | 404 |
| 48 | load_preference | fail | `` | 401/403 |
| 157 | api | fail | `'Preference value error: '.$e->getMessage(` | 422 |
| 163 | api | dbError | `` | 500 |

## app/Controllers/API/SettingAPIController.php

| Line | Function | Kind | Message | Suggested |
|---|---|---|---|---|
| 94 | api | fail | `'Missing setting value'` | 422 |
| 100 | api | fail | `'Preference value error: '.$e->getMessage(` | 422 |
| 106 | api | dbError | `` | 500 |

## app/Controllers/API/ShowAPIController.php

| Line | Function | Kind | Message | Suggested |
|---|---|---|---|---|
| 78 | postList | fail | `'This should never happen'` | 400 |
| 195 | api | fail | `` | 401/403 |
| 232 | api | fail | `"There's already an episode with the same season & episode number"` | 409 |
| 240 | api | fail | `"This episode cannot have two parts because {$next_part->toURL()} already exists."` | 409 |
| 247 | api | fail | `'Show entries cannot be converted to episodes via the interface.'` | 409 |
| 275 | api | fail | `'Please specify an air date & time'` | 422 |
| 277 | api | fail | `'Air dates before October 10th` | 400 |
| 298 | api | dbError | `'Show entry creation failed'` | 500 |
| 305 | api | dbError | `'Updating show entry failed'` | 500 |
| 311 | api | dbError | `` | 500 |
| 313 | api | success | `'Episode deleted successfully'` | 200 |
| 403 | voteApi | fail | `` | 401/403 |
| 406 | voteApi | fail | `'You can only vote on this episode after it has aired.'` | 422 |
| 410 | voteApi | fail | `"You already voted for this {$this->show->type}"` | 409 |
| 425 | voteApi | dbError | `` | 500 |
| 515 | guideRelationsApi | fail | `` | 401/403 |
| 619 | next | fail | `"The show is on hiatus` | 400 |
| 658 | prefill | fail | `` | 401/403 |
| 663 | prefill | fail | `'No last added episode found'` | 400 |

## app/Controllers/API/TagAPIController.php

| Line | Function | Kind | Message | Suggested |
|---|---|---|---|---|
| 85 | autocomplete | fail | `` | 401/403 |
| 94 | autocomplete | fail | `"This tag is already a synonym of <strong>{$Tag->synonym->name}</strong>.<br>Would you ...` | 409 |
| 158 | recountUses | fail | `` | 401/403 |
| 178 | recountUses | success | `` | 200 |
| 198 | load_tag | fail | `'Missing tag ID'` | 422 |
| 202 | load_tag | fail | `'This tag does not exist'` | 404 |
| 298 | api | fail | `'<p>This tag is currently used on '.CoreUtils::makePlural('appearance'` | 400 |
| 307 | api | success | `'Tag deleted successfully'` | 200 |
| 329 | api | fail | `'A tag with the same name and type already exists'` | 409 |
| 342 | api | dbError | `` | 500 |
| 347 | api | success | `"The tag was created` | 200 |
| 351 | api | success | `"The tag was created` | 200 |
| 422 | synonymApi | fail | `"The selected tag is already a synonym of the \"{$this->tag->synonym->name}\" (".Tags::...` | 409 |
| 432 | synonymApi | fail | `'Target tag does not exist'` | 404 |
| 434 | synonymApi | fail | `"The selected tag is already a synonym of the \"{$target->synonym->name}\" (".Tags::TAG...` | 409 |
| 447 | synonymApi | fail | `'Creating tag synonym failed` | 400 |
| 466 | synonymApi | success | `'Tag synonyms created'` | 200 |
| 483 | synonymApi | dbError | `'Could not update tag'` | 500 |

## app/Controllers/API/UserAPIController.php

| Line | Function | Kind | Message | Suggested |
|---|---|---|---|---|
| 57 | sessionApi | fail | `'Missing session ID'` | 422 |
| 61 | sessionApi | fail | `'This session does not exist'` | 404 |
| 63 | sessionApi | fail | `'You are not allowed to delete this session'` | 403 |
| 67 | sessionApi | success | `'Session successfully removed'` | 200 |
| 121 | roleApi | fail | `` | 401/403 |
| 124 | roleApi | fail | `'Missing user ID'` | 422 |
| 128 | roleApi | fail | `'User not found'` | 404 |
| 131 | roleApi | fail | `'You cannot modify your own group'` | 400 |
| 133 | roleApi | fail | `'You can only modify the group of users who are in the same or a lower-level group than...` | 422 |
| 223 | passwordApi | fail | `'The specified new password is a known compromised password present in at least one dat...` | 400 |
| 237 | passwordApi | dbError | `'Could not set the password due to a database error'` | 500 |
| 242 | passwordApi | dbError | `'Could not set the password due to a database error'` | 500 |
| 245 | passwordApi | success | `'Your new password has been set successfully. As a security precaution your existing se...` | 200 |
| 325 | emailApi | fail | `'You are trying to use same e-mail address '.($same_user ? 'you' : 'this user').' alrea...` | 409 |
| 332 | emailApi | fail | `'You will need to set a password first before changing your e-mail address'` | 400 |
| 340 | emailApi | fail | `'This e-mail address is already in use by another user'` | 409 |
| 345 | emailApi | fail | `'There was an issue while trying to send a confirmation e-mail` | 400 |
| 348 | emailApi | success | `'A confirmation e-mail has been sent to the specified address with a link to verify you...` | 200 |
| 403 | verifyApi | fail | `'The specified validation hash is either invalid or has expired'` | 401 |
| 417 | verifyApi | success | `'Your e-mail address has been added to our do-not-send list successfully.'` | 200 |
| 421 | verifyApi | fail | `'Could not update the e-mail address in the database.'` | 400 |
| 424 | verifyApi | success | `'Your e-mail address has been verified successfully.'` | 200 |
| 469 | contribCacheApi | fail | `'You are not allowed to clear contribution caches'` | 403 |
| 472 | contribCacheApi | fail | `'Missing user ID'` | 422 |
| 476 | contribCacheApi | fail | `'The specified user does not exist'` | 404 |
| 484 | contribCacheApi | success | `'Contributions cache successfully cleared'` | 200 |

## app/Controllers/API/UsersAPIController.php

| Line | Function | Kind | Message | Suggested |
|---|---|---|---|---|
| 134 | me | failApi | `` | 401/403 |

## app/Controllers/AppearanceController.php

| Line | Function | Kind | Message | Suggested |
|---|---|---|---|---|
| 81 | tagChanges | fail | `` | 401/403 |

## app/Controllers/ColorGuideController.php

| Line | Function | Kind | Message | Suggested |
|---|---|---|---|---|
| 197 | guide | fail | `'The ElasticSearch server is currently down and search is not available` | 503 |
| 210 | guide | fail | `'Your search returned no results.'` | 404 |

## app/Controllers/DiscordAuthController.php

| Line | Function | Kind | Message | Suggested |
|---|---|---|---|---|
| 34 | __construct | fail | `` | 401/403 |
| 128 | setTarget | fail | `` | 401/403 |
| 131 | setTarget | fail | `'You must be bound to a Discord user to perform this action'` | 422 |
| 144 | sync | fail | `'The Discord account must be linked before syncing'` | 422 |
| 147 | sync | fail | `'The account information was last updated '.Time::format($discordUser->last_synced->get...` | 422 |
| 189 | unlink | fail | `'Revoking access failed` | 400 |
| 197 | unlink | success | `"$Your Discord account was successfully unlinked.".($this->same_user` | 200 |

## app/Controllers/Traits/ColorGuideAccessTrait.php

| Line | Function | Kind | Message | Suggested |
|---|---|---|---|---|
| 50 | load_appearance | fail | `'Missing appearance ID'` | 422 |

## app/CoreUtils.php

| Line | Function | Kind | Message | Suggested |
|---|---|---|---|---|
| 157 | badRequest | fail | `'HTTP 400: Bad Request (e.g. invalid characters in the URL)'` | 422 |
| 174 | noPerm | fail | `"HTTP 403: You don't have permission to access {$_SERVER['REQUEST_URI']}"` | 403 |
| 191 | notFound | fail | `"HTTP 404: Endpoint ({$_SERVER['REQUEST_URI']}) does not exist"` | 404 |
| 208 | notAllowed | fail | `"HTTP 405: The endpoint {$_SERVER['REQUEST_URI']} does not support the {$_SERVER['REQUE...` | 400 |
| 225 | roleGate | fail | `'This API is currently under testing and is not available to all users` | 400 |
| 282 | loadPage | fail | `"The requested endpoint ($path) does not support JSON responses"` | 400 |
| 883 | checkStringValidity | fail | `$Error` | 422 |
| 1182 | checkDeviationInClub | fail | `$errmsg` | 400 |

## app/GlobalSettings.php

| Line | Function | Kind | Message | Suggested |
|---|---|---|---|---|
| 40 | set | fail | `"Key $name is not allowed"` | 403 |
| 78 | process | fail | `"You cannot change the $name setting"` | 400 |

## app/Image.php

| Line | Function | Kind | Message | Suggested |
|---|---|---|---|---|
| 32 | checkType | fail | `'This type of image is now allowed: '.$imageSize['mime']` | 422 |
| 36 | checkType | fail | `'The uploaded file is not an image'` | 400 |
| 55 | checkSize | fail | `"The image's ".(` | 400 |

## app/ImageProvider.php

| Line | Function | Kind | Message | Suggested |
|---|---|---|---|---|
| 172 | setUrls | fail | `"The specified {$this->provider} upload could not be found` | 404 |

## app/Input.php

| Line | Function | Kind | Message | Suggested |
|---|---|---|---|---|
| 194 | _validate | fail | `'Link URL does not appear to be a valid link'` | 400 |
| 302 | _outputError | fail | `$message` | 400 |

## app/Models/Appearance.php

| Line | Function | Kind | Message | Suggested |
|---|---|---|---|---|
| 956 | deleteSprite | fail | `'File could not be deleted'` | 400 |
| 966 | checkCreatePermission | fail | `"You don't have permission to add appearances to the official Color Guide"` | 403 |
| 972 | checkCreatePermission | fail | `"You don't have enough slots to create another appearance. Delete other ones or finish ...` | 400 |
| 975 | checkCreatePermission | fail | `Appearances::PCG_APPEARANCE_MAKE_DISABLED` | 400 |

## app/Models/DiscordMember.php

| Line | Function | Kind | Message | Suggested |
|---|---|---|---|---|
| 147 | updateAccessToken | fail | `'The Discord account link got severed` | 400 |
| 177 | sync | fail | `'The site is no longer authorized to access the Discord account data` | 400 |

## app/Models/Notification.php

| Line | Function | Kind | Message | Suggested |
|---|---|---|---|---|
| 106 | safeMarkRead | fail | `"Mark read error: {$e->getMessage()}"` | 422 |

## app/Models/Post.php

| Line | Function | Kind | Message | Suggested |
|---|---|---|---|---|
| 394 | approve | dbError | `` | 500 |

## app/Posts.php

| Line | Function | Kind | Message | Suggested |
|---|---|---|---|---|
| 93 | checkPostDetails | fail | `'Description cannot be empty'` | 409 |
| 107 | checkPostDetails | fail | `'Missing request type'` | 422 |
| 151 | checkImage | fail | `$e->getMessage(` | 400 |
| 158 | checkImage | fail | `"This exact image has already been used for a {$already_used->toAnchor($kind` | 409 |
| 183 | checkPostFinishingImage | fail | `"This exact deviation has already been marked as the finished version of  a {$already_u...` | 409 |
| 191 | checkPostFinishingImage | fail | `"Could not fetch local user data for username: $cached_deviation->author"` | 400 |
| 196 | checkPostFinishingImage | fail | `"You've linked to an image which was not submitted by $person. If this was intentional` | 400 |
| 210 | checkPostFinishingImage | fail | `'The finished vector must be uploaded to DeviantArt` | 422 |
| 213 | checkPostFinishingImage | fail | `$e->getMessage(` | 400 |
| 311 | checkReserveAs | fail | `'User to reserve as does not exist'` | 404 |
| 313 | checkReserveAs | fail | `'The specified user does not have permission to reserve posts` | 403 |

## app/UserPrefs.php

| Line | Function | Kind | Message | Suggested |
|---|---|---|---|---|
| 86 | set | fail | `"Key $name is not allowed"` | 403 |
| 120 | reset | fail | `"Key $key is not allowed"` | 403 |
| 173 | process | fail | `"You cannot change the $name preference"` | 400 |
| 179 | process | fail | `"$name is an internal setting and cannot be modified by users"` | 409 |

## app/Users.php

| Line | Function | Kind | Message | Suggested |
|---|---|---|---|---|
| 154 | checkReservationLimitReached | fail | `"You've already reserved {$resserved_count} images` | 409 |
| 455 | validateCurrentPassword | fail | `'The provided current password is incorrect'` | 400 |
| 464 | sendEmailValidation | fail | `'The specified email address has been added to our do-not-send list. If you are the own...` | 400 |
| 475 | sendEmailValidation | fail | `'A confirmation email was sent to this address recently` | 400 |
| 483 | sendEmailValidation | fail | `'Could not generate a secure verification link` | 400 |
| 507 | validateEmail | fail | `'The provided e-mail address does not pass our validity checks` | 400 |
