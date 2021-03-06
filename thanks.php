<?php
/* ====================
[BEGIN_COT_EXT]
Hooks=standalone
[END_COT_EXT]
==================== */

/**
 * Thanks main script
 *
 * @package thanks
 * @version 1.0
 * @author Trustmaster
 * @copyright Copyright (c) Vladimir Sibirov 2011
 * @license BSD
 */

defined('COT_CODE') or die('Wrong URL');

$ext = cot_import('ext', 'G', 'ALP');
$item = cot_import('item', 'G', 'INT');
$user = cot_import('user', 'G', 'INT');

if ($a == 'thank' && !empty($ext) && $item > 0)
{
	if ($ext == 'page')
	{
		require_once cot_incfile('page', 'module');
		$res = $db->query("SELECT page_ownerid FROM $db_pages WHERE page_id = $item");
	}
	else if ($ext == 'forums')
	{
		require_once cot_incfile('forums', 'module');
		$res = $db->query("SELECT fp_posterid FROM $db_forum_posts WHERE fp_id = $item");
	}
	else
	{
		$res = false;
	}
	if ($res && $res->rowCount() == 1 && $usr['auth_write'])
	{
		$user = $res->fetchColumn();
	}
	else
	{
		$ext['status'] = '400 Bad Request';
		cot_die();
	}
	
	$status = thanks_check($user, $usr['id'], $ext, $item);
	switch ($status)
	{
		case THANKS_ERR_MAXDAY:
			header('403 Forbidden');
			cot_error('thanks_err_maxday');
			break;
		case THANKS_ERR_MAXUSER:
			header('403 Forbidden');
			cot_error('thanks_err_maxuser');
			break;
		case THANKS_ERR_ITEM:
			header('403 Forbidden');
			cot_error('thanks_err_item');
			break;
		case THANKS_ERR_NONE:
			thanks_add($user, $usr['id'], $ext, $item);
			cot_message('thanks_done');
			break;
	}
	$t = new XTemplate(cot_tplfile('thanks.add', 'plug'));
	$t->assign(array(
		'THANKS_BACK_URL' => $_SERVER['HTTP_REFERER']
	));
	cot_display_messages($t);
}
else
{
	if (!$user)
	{
		$user = $usr['id'];
	}
	// List all user's thanks here
	require_once cot_incfile('page', 'module');
	require_once cot_incfile('forums', 'module');
	
	$res = $db->query("SELECT t.*, pag.page_alias, pag.page_title, pag.page_cat, ft.ft_title, p.fp_cat, u.user_name
		FROM $db_thanks AS t
			LEFT JOIN $db_users AS u ON t.th_fromuser = u.user_id
			LEFT JOIN $db_pages AS pag ON t.th_ext = 'page' AND t.th_item = pag.page_id
			LEFT JOIN $db_forum_posts AS p ON t.th_ext = 'forums' AND t.th_item = p.fp_id
				LEFT JOIN $db_forum_topics AS ft ON p.fp_id > 0 AND p.fp_topicid = ft.ft_id
		WHERE th_touser = $user
		ORDER BY th_date DESC");
	foreach ($res->fetchAll() as $row)
	{
		$t->assign(array(
				'THANKS_ROW_ID' => $row['th_id'],
				'THANKS_ROW_DATE' => cot_date('datetime_medium', cot_date2stamp($row['th_date'])),
				'THANKS_ROW_FROM_URL' => cot_url('users', 'm=details&id='.$row['th_fromuser'].'&u='.urlencode($row['user_name'])),
				'THANKS_ROW_FROM_NAME' => htmlspecialchars($row['user_name'])
			));
		if (!empty($row['page_title']))
		{
			// For a page
			$t->assign(array(
				'THANKS_ROW_URL' => empty($row['page_alias']) ? cot_url('page', 'id='.$row['th_item']) : cot_url('page', 'al='.$row['page_alias']),
				'THANKS_ROW_CAT_TITLE' => htmlspecialchars($structure['page'][$row['page_cat']]['title']),
				'THANKS_ROW_CAT_URL' => cot_url('page', 'c='.$row['page_cat']),
				'THANKS_ROW_TITLE' => htmlspecialchars($row['page_title'])
			));
		}
		else if (!empty($row['ft_title']))
		{
			// For a page
			$t->assign(array(
				'THANKS_ROW_URL' => cot_url('forums', 'm=posts&id='.$row['th_item']),
				'THANKS_ROW_CAT_TITLE' => htmlspecialchars($structure['forums'][$row['fp_cat']]['title']),
				'THANKS_ROW_CAT_URL' => cot_url('forums', 'm=topics&s='.$row['fp_cat']),
				'THANKS_ROW_TITLE' => htmlspecialchars($row['ft_title'])
			));
		}
		$t->parse('MAIN.THANKS_ROW');
	}
	
	$name = $user == $usr['id'] ?  $usr['name'] : $db->query("SELECT user_name FROM $db_users WHERE user_id = $user")->fetchColumn();
	
	$t->assign(array(
		'THANKS_USER_NAME' => htmlspecialchars($name),
		'THANKS_USER_URL' => cot_url('users', 'm=details&id='.$user.'&u='.$name)
	));
}

?>
