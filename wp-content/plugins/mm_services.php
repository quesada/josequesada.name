<?php
// http://de.lirio.us/

function mm_post_delirious ($curl, $vars)
{
  extract ($vars);
  if (empty ($extra))
  {
    $extra = 'http://de.lirio.us/';
  }
  elseif (substr ($extra, - 1, 1) != '/')
  {
    $extra .= '/';
  }
  if ($extra == 'http://de.lirio.us/')
  {
    $curl->setRedirect (false);
    $page = $curl->post ($extra.'login.php', array ('username' => $username, 'password' => $password, 'submitted' => 'Log In', 'query' => ''));
    if ($err = $curl->getError ())
    {
      return $err;
    }
    if (302 != $curl->getHttpCode ())
    {
      return ASP_ERROR_LOGIN;
    }
    $page = $curl->post ($extra.'bookmarks.php/'.$username.'?action=add', array ('address' => $link, 'title' => $title, 'tags' => implode (',', $tags), 'description' => $extended, 'status' => '0', 'submitted' => 'Add Bookmark'));
    if ($err = $curl->getError ())
    {
      return $err;
    }

    //if (302 != $curl->getHttpCode ())
    //{
    // return ASP_ERROR_POST;
    //}

    return ASP_ERROR_OK;
  }
  $curl->auth ($username, $password);
  $page = $curl->get ($extra.'api/posts/add', array ('url' => $link, 'tags' => implode (' ', $tags), 'description' => $title, 'extended' => $extended, 'shared' => 'yes'));
  if ($curl->getHttpCode () == 0)
  {
    return ASP_ERROR_PROXY;
  }
  if ($err = $curl->getError ())
  {
    return $err;
  }
  if (401 == $curl->getHttpCode ())
  {
    return ASP_ERROR_LOGIN;
  }
  if (!strpos ($page, 'done') || (200 != $curl->getHttpCode ()))
  {
    return ASP_ERROR_POST;
  }
  return ASP_ERROR_OK;
}

//https://www.propeller.com

function mm_post_propeller ($curl, $vars)
{
  extract ($vars);
  $extra = trim ($extra);
  if (empty ($extra))
  {
    $extra = "1";
  }
  $page = $curl->post ('https://www.propeller.com/signin/', array ('referer' => 'http://www.bibsonomy.org/', 'alias' => $username, 'pwd' => $password, 'formsubmit' => 'Sign In'));
  if ($err = $curl->getError ())
  {
    return $err;
  }
  if (200 != $curl->getHttpCode ())
  {
    return ASP_ERROR_LOGIN;
  }
  if (substr_count ($page, 'The login you specified is not') || substr_count ($page, "haven't yet confirmed your account") || substr_count ($page, "haven't yet confirmed your account"))
  {
    return ASP_ERROR_LOGIN;
  }
  if (is_array ($tags))
  {
    foreach ($tags as $ky => $tg)
    {
      if (strlen ($tg) < 3)
      {
        unset ($tags[$ky]);
      }
    }
  }
  if (!is_array ($tags) || empty ($tags))
  {
    $tags = explode (' ', $title);
  }
  $tags = implode (' ', $tags);
  $extended = trim (strip_tags ($extended));
  if (strlen ($title) < 10 || strlen ($extended) < 13)
  {
    return ASP_ERROR_POST;
  }
  $page = $curl->post ('http://www.propeller.com/submit', '', 'http://www.propeller.com/');
  $postdata = array(
    'storyUrl'   => $link,
    'storyTitle' => $title,
    'storyTags'  => implode (' ',
    $tags),
    'storyText'    => $extended,
    'storyChannel' => $extra,
    'storySubmit'  => 'Check My Story',
  );
  $hiddens = mm_search ('type="hidden"', ">", $page);
  foreach ($hiddens as $hidden)
  {
    $hd_name            = mm_search ('name="', "\"", $hidden, false);
    $hd_name            = trim ($hd_name[0]);
    $hd_val             = mm_search ('value="', "\"", $hidden, false);
    $hd_val             = trim ($hd_val[0]);
    $postdata[$hd_name] = $hd_val;
  }
  if ($err = $curl->getError ())
  {
    return $err;
  }
  $page                    = $curl->post ('http://www.propeller.com/submit/checkstory', $postdata, 'http://www.propeller.com/submit');
  $postdata['storySubmit'] = 'Submit this Story';
  $page                    = $curl->post ('http://www.propeller.com/submit/checkstory', $postdata, 'http://www.propeller.com/submit');
  if ($err = $curl->getError ())
  {
    return $err;
  }
  if ( (200 != $curl->getHttpCode ()))
  {
    return ASP_ERROR_POST;
  }
  return ASP_ERROR_OK;
}

//http://www.searchles.com

function mm_post_searchles ($curl, $vars)
{
  extract ($vars);
  $extra = trim ($extra);
  if (empty ($extra))
  {
    $extra = "1";
  }
  $page = $curl->post ('http://www.searchles.com/login', array ('login[username]' => $username, 'login[password]' => $password, 'return' => 'http://www.searchles.com/', 'formsubmit' => 'Sign In'));
  if ($err = $curl->getError ())
  {
    return $err;
  }
  if (200 != $curl->getHttpCode ())
  {
    return ASP_ERROR_LOGIN;
  }
  if (substr_count ($page, 'Authentication failed'))
  {
    return ASP_ERROR_LOGIN;
  }
  if (!is_array ($tags) || empty ($tags))
  {
    $tags = explode (' ', $title);
  }
  $extended     = trim (strip_tags ($extended));
  $page         = $curl->post ('http://www.searchles.com/links/add_link', '', 'http://www.searchles.com/my');
  $Post_link_id = mm_search ('/links/add_link/', '"', $page, false);
  $Post_link    = 'http://www.searchles.com/links/add_link/'.$Post_link_id[0];
  $postdata = array(
    'link[url]'          => $link,
    'description[title]' => $title,
    'tags[tags]'         => implode (',',
    $tags),
    'description[desc]' => $extended,
    'storyChannel'      => $extra,
    'submit'            => 'Submit Post',
  );
  $hiddens = mm_search ('type="hidden"', ">", $page);
  foreach ($hiddens as $hidden)
  {
    $hd_name            = mm_search ('name="', "\"", $hidden, false);
    $hd_name            = trim ($hd_name[0]);
    $hd_val             = mm_search ('value="', "\"", $hidden, false);
    $hd_val             = trim ($hd_val[0]);
    $postdata[$hd_name] = $hd_val;
  }
  if ($err = $curl->getError ())
  {
    return $err;
  }
  $page = $curl->post ($Post_link, $postdata, 'http://www.searchles.com/links/add_link/');
  if ($err = $curl->getError ())
  {
    return $err;
  }
  if ( (200 != $curl->getHttpCode ()) && (500 != $curl->getHttpCode ()))
  {
    return ASP_ERROR_POST;
  }
  return ASP_ERROR_OK;
}

//http://www.bibsonomy.org/

function mm_post_bibsonomy ($curl, $vars)
{
  extract ($vars);
  $page = $curl->post ('http://www.bibsonomy.org/login_process', array ('referer' => 'http://www.bibsonomy.org/', 'userName' => $username, 'loginPassword' => $password, 'submit' => 'log in'));
  if ($err = $curl->getError ())
  {
    return $err;
  }
  if (200 != $curl->getHttpCode ())
  {
    return ASP_ERROR_LOGIN;
  }
  if (substr_count ($page, 'Access denied: Please check your') || substr_count ($page, 'Please enter a valid password'))
  {
    return ASP_ERROR_LOGIN;
  }
  $pst_array = array(
    'url' => $link,
    'submit' => 'check',
  );
  $page = $curl->post ('http://www.bibsonomy.org/ShowBookmarkEntry', $pst_array);
  if (count ($tags) == 0)
  {
    $tags[] = $title;
  }
  $postdata = array(
    'url'         => $link,
    'description' => $title,
    'tags'        => implode (' ',
    $tags),
    'extended' => $extended,
    'group'    => 'public',
    'submit'   => 'save',
  );
  $hiddens = mm_search ('type="hidden"', ">", $page);
  foreach ($hiddens as $hidden)
  {
    $hd_name            = mm_search ('name="', "\"", $hidden, false);
    $hd_name            = trim ($hd_name[0]);
    $hd_val             = mm_search ('value="', "\"", $hidden, false);
    $hd_val             = trim ($hd_val[0]);
    $postdata[$hd_name] = $hd_val;
  }
  if ($err = $curl->getError ())
  {
    return $err;
  }
  $page = $curl->post ('http://www.bibsonomy.org/bookmark_posting_process', $postdata, 'http://www.netvouz.com/action/addBookmark');
  if ($err = $curl->getError ())
  {
    return $err;
  }
  if ( (200 != $curl->getHttpCode ()))
  {
    return ASP_ERROR_POST;
  }
  return ASP_ERROR_OK;
}

//http://www.faves.com/

function mm_post_faves ($curl, $vars)
{
  extract ($vars);

  //$curl->setRedirect(false);

  $page = $curl->post ('https://secure.faves.com/signIn', array ('rUsername' => $username, 'rPassword' => $password, 'action' => 'Sign In'));
  if ($err = $curl->getError ())
  {
    return $err;
  }
  if (200 != $curl->getHttpCode ())
  {
    return ASP_ERROR_LOGIN;
  }
  if (substr_count ($page, 'Invalid login or password'))
  {
    return ASP_ERROR_LOGIN;
  }
  $page = $curl->get ('http://faves.com/CreateDot.aspx');
  $postdata = array(
    'rateSelect'  => '1',
    'urlText'     => $link,
    'subjectText' => $title,
    'tagsText'    => implode (',',
    $tags),
    'noteText'       => $extended,
    'shareSelect'    => 'Public',
    'addFriendsText' => '',
    'publishButton'  => 'Publish',
  );
  $hiddens = mm_search ('type="hidden"', ">", $page);
  foreach ($hiddens as $hidden)
  {
    $hd_name            = mm_search ('name="', "\"", $hidden, false);
    $hd_name            = trim ($hd_name[0]);
    $hd_val             = mm_search ('value="', "\"", $hidden, false);
    $hd_val             = trim ($hd_val[0]);
    $postdata[$hd_name] = $hd_val;
  }
  if ($err = $curl->getError ())
  {
    return $err;
  }
  $page = $curl->post ('http://faves.com/CreateDot.aspx', $postdata, 'http://faves.com/CreateDot.aspx');
  if ($err = $curl->getError ())
  {
    return $err;
  }
  if ( (200 != $curl->getHttpCode ()))
  {
    return ASP_ERROR_POST;
  }
  return ASP_ERROR_OK;
}

//http://my.xilinus.com

function mm_post_xilinus ($curl, $vars)
{
  extract ($vars);

  //$curl->setRedirect(false);

  $page = $curl->post ('http://my.xilinus.com/login/login', array ('user_login' => $username, 'user_password' => $password, '' => 'Login'));
  if ($err = $curl->getError ())
  {
    return $err;
  }
  if (200 != $curl->getHttpCode ())
  {
    return ASP_ERROR_LOGIN;
  }
  if (substr_count ($page, 'Login or password incorrect'))
  {
    return ASP_ERROR_LOGIN;
  }
  if (count ($tags) == 0)
  {
    $tags[] = $title;
  }
  $page = $curl->get ('http://my.xilinus.com/link/add');

  //return $page;

  $postdata = array(
    'link[url]'   => $link,
    'group[name]' => $title,
    'tag_link'    => implode (' ',
    $tags),
    'link[public]' => '1',
  );

  //'group_id' =>'201133',

  $hiddens = mm_search ('type="hidden"', ">", $page);
  foreach ($hiddens as $hidden)
  {
    $hd_name            = mm_search ('name="', "\"", $hidden, false);
    $hd_name            = trim ($hd_name[0]);
    $hd_val             = mm_search ('value="', "\"", $hidden, false);
    $hd_val             = trim ($hd_val[0]);
    $postdata[$hd_name] = $hd_val;
  }
  if ($err = $curl->getError ())
  {
    return $err;
  }
  $page = $curl->post ('http://my.xilinus.com/link/add', $postdata, 'http://my.xilinus.com/link/add');
  if ($err = $curl->getError ())
  {
    return $err;
  }
  if (200 != $curl->getHttpCode ())
  {
    return ASP_ERROR_POST;
  }
  return ASP_ERROR_OK;
}

//http://buddymarks.com

function mm_post_buddymarks ($curl, $vars)
{
  extract ($vars);

  //$curl->setRedirect(false);

  $page = $curl->post ('http://buddymarks.com/login.php', array ('action' => 'login', 'bookmark_title' => '', 'bookmark_url' => '', 'form_username' => $username, 'form_password' => $password, 'login_type' => 'permanent', '' => 'Login'));
  if ($err = $curl->getError ())
  {
    return $err;
  }
  if (200 != $curl->getHttpCode ())
  {
    return ASP_ERROR_LOGIN;
  }
  if (substr_count ($page, 'Invalid username or password'))
  {
    return ASP_ERROR_LOGIN;
  }
  if (!is_array ($tags) || empty ($tags))
  {
    $tags = explode (' ', $title);
  }
  sleep (2);
  $page = $curl->get ('http://buddymarks.com/add_bookmark.php');
  $postdata = array(
    'form_buddymark'   => 'true',
    'form_public_link' => 'true',
    'form_cat_id'      => '4',
    'bookmark_url'     => $link,
    'bookmark_title'   => $title,
    'form_tags'        => implode (',',
    $tags),
    'form_description' => $extended,
    'form_private'     => '0',
    ''                 => 'Add Bookmark',
  );
  if (substr_count ($page, 'first category'))
  {
    $firs_cat                    = mm_search ('<option value=', 'first category', $page, false);
    $firs_cat                    = $firs_cat[count ($firs_cat) - 1];
    $firs_cat_id                 = mm_search ('\'', '\'', $firs_cat, false);
    $firs_cat_id                 = trim ($firs_cat_id[count ($firs_cat_id) - 1]);
    $postdata['form_group_type'] = 'existing';
    $postdata['form_group_id']   = $firs_cat_id;
  }
  else
  {
    $postdata['form_group_type'] = 'new';
    $postdata['form_group_title'] = 'first category';
  }
  $hiddens = mm_search ('type=\'hidden\'', ">", $page);
  foreach ($hiddens as $hidden)
  {
    $hd_name            = mm_search ('name=\'', "'", $hidden, false);
    $hd_name            = trim ($hd_name[0]);
    $hd_val             = mm_search ('value=\'', "'", $hidden, false);
    $hd_val             = trim ($hd_val[0]);
    $postdata[$hd_name] = $hd_val;
  }
  if ($err = $curl->getError ())
  {
    return $err;
  }
  $postdata['back_url'] = 'http://buddymarks.com/';
  sleep (2);
  $page = $curl->post ('http://buddymarks.com/add_bookmark.php?action=insert_bookmark', $postdata, 'http://buddymarks.com/add_bookmark.php');
  if ($err = $curl->getError ())
  {
    return $err;
  }
  if ( (200 != $curl->getHttpCode ()) || substr_count ($page, 'ERROR:'))
  {
    return ASP_ERROR_POST;
  }
  return ASP_ERROR_OK;
}

//////////////////////////// 17/9/07 /////////////////////////////////
// connectedy

function mm_post_connectedy ($curl, $vars)
{
  extract ($vars);
  $page = $curl->post ('http://www.connectedy.com/login.php', array ('user' => $username, 'password' => $password, '' => 'Login'));
  if ($err = $curl->getError ())
  {
    return $err;
  }
  if (200 != $curl->getHttpCode ())
  {
    return ASP_ERROR_LOGIN;
  }
  if (substr_count ($page, 'invalid login'))
  {
    return ASP_ERROR_LOGIN;
  }
  $page = $curl->get ('http://www.connectedy.com/add-link.php?category=0');
  $postdata = array(
    'url'    => $link,
    'title'  => $title,
    'dest'   => '0',
    'shared' => '1',
    ''       => 'Add',
  );
  $hiddens = mm_search ('type="hidden"', ">", $page);
  foreach ($hiddens as $hidden)
  {
    $hd_name            = mm_search ('name="', "\"", $hidden, false);
    $hd_name            = trim ($hd_name[0]);
    $hd_val             = mm_search ('value="', "\"", $hidden, false);
    $hd_val             = trim ($hd_val[0]);
    $postdata[$hd_name] = $hd_val;
  }
  if ($err = $curl->getError ())
  {
    return $err;
  }
  $page = $curl->post ('http://www.connectedy.com/modify.php', $postdata, 'http://www.connectedy.com/add-link.php?category=0');
  if ($err = $curl->getError ())
  {
    return $err;
  }
  if ( (200 != $curl->getHttpCode ()))
  {
    return ASP_ERROR_POST;
  }
  return ASP_ERROR_OK;
}

// mister_wong

function mm_post_misterWong ($curl, $vars)
{
  extract ($vars);
  $extra = trim ($extra);
  if (empty ($extra))
  {
    $extra = 'com';
  }
  $page = $curl->post ('http://www.mister-wong.'.$extra.'/?action=login', array ('user_name' => $username, 'user_password' => $password, 'login' => 'Login'));
  if ($err = $curl->getError ())
  {
    return $err;
  }
  if (200 != $curl->getHttpCode ())
  {
    return ASP_ERROR_LOGIN;
  }

  /////// com ////////

  if (substr_count ($page, 'The user entered does not exist') || substr_count ($page, 'The password entered is not correct') || substr_count ($page, 'You have not entered a password') || substr_count ($page, 'Sie haben versucht sich in einer fremdsprachigen'))
  {
    return ASP_ERROR_LOGIN;
  }
  if (substr_count ($page, 'Du hast kein Passwort eingegeben') || substr_count ($page, 'Der eingegebene Benutzer existiert nicht') || substr_count ($page, 'Das eingegebene Passwort ist falsch'))
  {
    return ASP_ERROR_LOGIN;
  }
  if (substr_count ($page, 'Vous n\'avez pas donné de mot de passe') || substr_count ($page, 'L\'utilisateur que vous cherchez n\'existe pas') || substr_count ($page, 'Le mot de passe que vous avez donné est faux'))
  {
    return ASP_ERROR_LOGIN;
  }
  if (substr_count ($page, 'No has ingresado una clave') || substr_count ($page, 'El usuario ingresado no existe') || substr_count ($page, 'La clave ingresada es errónea'))
  {
    return ASP_ERROR_LOGIN;
  }
  if (strlen ($extended) > 200)
  {
    $extended = substr ($extended, 0, 197).'..';
  }
  $page = $curl->get ('http://www.mister-wong.'.$extra.'/add_url/');
  if (count ($tags) == 0)
  {
    $tags[] = $title;
  }
  $postdata = array(
    'bm_url'         => $link,
    'bm_description' => $title,
    'bm_notice'      => $extended,
    'bm_tags'        => implode (' ',
    $tags),
    'bm_status' => 'public',
    'addurl' => 'Save bookmark',
  );
  $hiddens = mm_search ('type="hidden"', ">", $page);
  foreach ($hiddens as $hidden)
  {
    $hd_name            = mm_search ('name="', "\"", $hidden, false);
    $hd_name            = trim ($hd_name[0]);
    $hd_val             = mm_search ('value="', "\"", $hidden, false);
    $hd_val             = trim ($hd_val[0]);
    $postdata[$hd_name] = $hd_val;
  }
  if ($err = $curl->getError ())
  {
    return $err;
  }
  $page = $curl->post ('http://www.mister-wong.'.$extra.'/add_url/', $postdata, 'http://www.mister-wong.'.$extra.'/add_url/');
  return $page;
  if ($err = $curl->getError ())
  {
    return $err;
  }
  if ( (200 != $curl->getHttpCode ()))
  {
    return ASP_ERROR_POST;
  }
  return ASP_ERROR_OK;
}

// blinklist.com
// no API

function mm_post_blinklist ($curl, $vars)
{
  extract ($vars);
  $page = $curl->post ('http://www.blinklist.com/profile/signin.php', array ('Username' => $username, 'Password' => $password));
  if ($err = $curl->getError ())
  {
    return $err;
  }
  if (strpos ($page, 'check your username') || (200 != $curl->getHttpCode ()))
  {
    return ASP_ERROR_LOGIN;
  }
  $page = $curl->post ('http://www.blinklist.com/index.php?Action=Blink/blink.php', array ('Name' => $title, 'Url' => $link, 'Tag' => implode (',', $tags), 'Description' => $extended, 'Address' => '', 'AdditionalMessage' => '', 'Rating' => '', 'Magic' => '', 'Domain' => '', 'Lid' => ''));
  if ($err = $curl->getError ())
  {
    return $err;
  }
  if (strpos ($page, 'BlackList') || (200 != $curl->getHttpCode ()))
  {
    return ASP_ERROR_POST;
  }
  return ASP_ERROR_OK;

  // site does no error checking
}

// del.icio.us

function mm_post_delicious ($curl, $vars)
{
  extract ($vars);
  $curl->auth ($username, $password);
  $page = $curl->get ('https://api.del.icio.us/v1/posts/add', array ('url' => $link, 'tags' => implode (' ', $tags), 'description' => $title, 'extended' => $extended, 'shared' => 'yes'));
  if ($err = $curl->getError ())
  {
    return $err;
  }
  if (401 == $curl->getHttpCode ())
  {
    return ASP_ERROR_LOGIN;
  }
  if (strpos ($page, 'something went wrong') || (200 != $curl->getHttpCode ()))
  {
    return ASP_ERROR_POST;
  }
  return ASP_ERROR_OK;
}

// myweb.search.yahoo.com
// No API

function mm_post_myweb ($curl, $vars)
{
  extract ($vars);
  $curl->setRedirect (false);
  $page = $curl->post ('https://login.yahoo.com/config/login', '.tries=1&.src=srch&.md5=&.hash=&.js=&.last=&promo=&.intl=us&.bypass=&.partner=&.yplus=&.emailCode=&pkg=&stepid=&.ev=&hasMsgr=0&.chkP=Y&.done='.urlencode ('http://myweb2.search.yahoo.com')."&.pd=srch_ver%3d0&login=".urlencode ($username)."&passwd=".urlencode ($password));
  if ($err = $curl->getError ())
  {
    return $err;
  }
  if (302 != $curl->getHttpCode ())
  {
    return ASP_ERROR_LOGIN;
  }
  $page = $curl->get ('http://myweb.yahoo.com/myresults/bookmarklet');
  preg_match ('#name=\.scrumb value="([^"]+)"#', $page, $match);
  if (!isset ($match[1]))
  {
    return ASP_ERROR_POST;
  }

  // Yahoo changed something

  $vars = $curl->makeQuery (array ('.scrumb' => $match[1], 'p' => '', 'ei' => 'UTF-8', 'myfr' => 'bmlt', 'onesummary' => 1, 'hl' => '0', 'ft' => '', 'v' => '1',

  // public

  'bkln' => '1', '.done' => 'http://myweb.yahoo.com/myresults/bookmarklet?ei=UTF-8', 'u' => $link, 't' => $title, 'tag' => implode (',', $tags), 'd' => $extended));
  $page = $curl->post ('http://myweb.yahoo.com/myresults/insertresult', $vars);
  if ($err = $curl->getError ())
  {
    return $err;
  }
  if (302 != $curl->getHttpCode ())
  {
    return ASP_ERROR_POST;
  }
  if (strpos ($curl->redirect_url, 'prserror') !== false)
  {
    return ASP_ERROR_POST;
  }
  return ASP_ERROR_OK;
}

// Blogmarks.net
// crappy API, so screen-scrape

function mm_post_blogmarks ($curl, $vars)
{
  extract ($vars);
  $page = $curl->post ('http://blogmarks.net/', array ('login' => $username, 'pwd' => $password, 'connect' => '1'));
  if ($err = $curl->getError ())
  {
    return $err;
  }
  if (!strpos ($page, 'disconnect') || (200 != $curl->getHttpCode ()))
  {
    return ASP_ERROR_LOGIN;
  }
  $page = $curl->get ('http://blogmarks.net/my/marks,new');
  preg_match ('#name="post-token" value="([^"]+)"#', $page, $match);
  if (!isset ($match[1]))
  {
    return ASP_ERROR_POST;
  }
  $post_token = $match[1];
  $page = $curl->post ('http://blogmarks.net/my/marks,new', array ('url' => $link, 'title' => $title, 'content' => $extended, 'public-tags' => implode (',', $tags), 'private-tags' => '', 'via' => '', 'visibility' => '0', 'referer' => 'http://blogmarks.net/my/marks', 'id' => '', 'post-token' => $post_token));
  if ($err = $curl->getError ())
  {
    return $err;
  }
  if (strpos ($page, 'not well formed') || (200 != $curl->getHttpCode ()))
  {
    return ASP_ERROR_POST;
  }
  return ASP_ERROR_OK;
}

// scuttle

function mm_post_scuttle ($curl, $vars)
{
  extract ($vars);
  if (empty ($extra))
  {
    $extra = 'http://scuttle.org/';
  }
  elseif (substr ($extra, - 1, 1) != '/')
  {
    $extra .= '/';
  }
  if ($extra == 'http://scuttle.org/')
  {
    $curl->setRedirect (false);
    $page = $curl->post ($extra.'login/', array ('username' => $username, 'password' => $password, 'submitted' => 'Log In', 'query' => ''));
    if ($err = $curl->getError ())
    {
      return $err;
    }
    if (302 != $curl->getHttpCode ())
    {
      return ASP_ERROR_LOGIN;
    }
    $page = $curl->post ($extra.'bookmarks/'.$username, array ('address' => $link, 'title' => $title, 'tags' => implode (',', $tags), 'description' => $extended, 'status' => '0', 'submitted' => 'Add Bookmark'));
    if ($err = $curl->getError ())
    {
      return $err;
    }
    if (302 != $curl->getHttpCode ())
    {
      return ASP_ERROR_POST;
    }
    return ASP_ERROR_OK;
  }
  $curl->auth ($username, $password);
  $page = $curl->get ($extra.'api/posts/add', array ('url' => $link, 'tags' => implode (',', $tags), 'description' => $title, 'extended' => $extended, 'shared' => 'yes'));
  if ($err = $curl->getError ())
  {
    return $err;
  }
  if (401 == $curl->getHttpCode ())
  {
    return ASP_ERROR_LOGIN;
  }

  //  if (!strpos($page, 'done') || (200 != $curl->getHttpCode())) {

  if (200 != $curl->getHttpCode ())
  {
    return ASP_ERROR_POST;
  }
  return ASP_ERROR_OK;
}

//scuttleplus

function mm_post_scuttlePLUS ($curl, $vars)
{
  extract ($vars);
  $extra = trim ($extra);
  if (empty ($extra))
  {
    $extra = 'http://scuttle.org/';
  }
  elseif (substr ($extra, - 1, 1) != '/')
  {
    $extra .= '/';
  }
  $curl->auth ($username, $password);
  $page = $curl->get ($extra.'api/posts_add.php', array ('url' => $link, 'tags' => implode (',', $tags), 'description' => $title, 'extended' => $extended, 'shared' => 'yes'));
  if ($curl->getHttpCode () == 0)
  {
    return ASP_ERROR_PROXY;
  }
  if (401 == $curl->getHttpCode ())
  {
    return ASP_ERROR_LOGIN;
  }
  if (!strpos ($page, 'done') || (200 != $curl->getHttpCode ()))
  {
    return ASP_ERROR_POST;
  }
  return ASP_ERROR_OK;
}

// Markaboo.com

function mm_post_markaboo ($curl, $vars)
{
  extract ($vars);
  $page = $curl->post ('http://www.markaboo.com/users/authenticate', array ('login' => $username, 'password' => $password, 'commit' => 'Log in'));
  if ($err = $curl->getError ())
  {
    return $err;
  }
  if (strpos ($page, 'errorExplanation') || (200 != $curl->getHttpCode ()))
  {
    return ASP_ERROR_LOGIN;
  }
  $curl->disableRedirect ();
  $page = $curl->post ('http://www.markaboo.com/resources/create', array ('resource[url]' => $link, 'resource[title]' => $title, 'resource[description]' => $extended, 'tag_list' => implode (',', $tags), 'resource[private]' => '0', 'resource[rating]' => '5', 'commit' => 'Create Bookmark'));
  if ($err = $curl->getError ())
  {
    return $err;
  }

  // we get redirected to $link after posting, so don't check for HTTP code

  if (strpos ($page, 'errorExplanation'))
  {
    return ASP_ERROR_POST;
  }
  return ASP_ERROR_OK;
}

// Linkagogo.com
// Poorly documented API. Can't post to "Society" via API.

function mm_post_linkagogo ($curl, $vars)
{
  extract ($vars);

  //curl_setopt($curl->curl, CURLOPT_COOKIE, 'cookies=Y; user=-1; userName=guest');

  $page = $curl->post ('http://www.linkagogo.com/go/Authenticate');
  $page = $curl->post ('http://www.linkagogo.com/go/Authenticate', array ('userName' => $username, 'code' => $password, 'btnLogin' => 'Login'));

  //curl_setopt($curl->curl, CURLOPT_COOKIE, '');

  if ($err = $curl->getError ())
  {
    return $err;
  }
  if (strpos ($page, 'Login') || (200 != $curl->getHttpCode ()))
  {
    return ASP_ERROR_LOGIN;
  }
  $home_folder = get_option ('mm_linkagogo_folder_'.$username);
  if (!$home_folder)
  {
    $page = $curl->get ('http://www.linkagogo.com/go/AddMenu');
    preg_match ('#<option value="([^"]+)">\s*Home</option>#si', $page, $match);
    if (!is_array ($match) && !isset ($match[1]))
    {
      return ASP_ERROR_POST;
    }
    $home_folder = $match[1];
    update_option ('mm_linkagogo_folder_'.$username, $home_folder);
  }
  $page = $curl->post ('http://www.linkagogo.com/go/AddMenu', array ('url' => $link, 'title' => $title, 'comments' => $extended, 'keywords' => implode (' ', $tags), 'favorites' => 'on', 'alias' => '', 'rating' => '4', 'remind' => '-9', 'days' => '', 'folder' => $home_folder, 'newFolder' => '', 'image' => '', 'user' => $username, 'password' => 'null', 'submit' => 'Add'));
  if ($err = $curl->getError ())
  {
    return $err;
  }
  if (strpos ($page, 'Please correct the following') || (200 != $curl->getHttpCode ()))
  {
    return ASP_ERROR_POST;
  }
  return ASP_ERROR_OK;
}

// BackFlip.com

function mm_post_backflip ($curl, $vars)
{
  extract ($vars);
  $extra = trim ($extra);
  $curl->setRedirect (false);
  $page = $curl->post ('http://www.backflip.com/perl/auth.pl?ls=01', array ('username' => $username, 'password' => $password));
  if ($err = $curl->getError ())
  {
    return $err;
  }
  if ( (302 != $curl->getHttpCode ()) || empty ($curl->redirect_url))
  {
    return ASP_ERROR_LOGIN;
  }
  $page = $curl->post ('http://www.backflip.com/perl/addMark.pl', array ('url' => $link, 'title' => $title, 'note' => $extended, 'folder' => $extra, 'close' => 'true', 'target' => '', 'source' => 'B', 'SubmitUrl' => 'Backflip It!'));
  if ($err = $curl->getError ())
  {
    return $err;
  }
  if (200 != $curl->getHttpCode ())
  {
    return ASP_ERROR_POST;
  }
  return ASP_ERROR_OK;
}

// Google.com/bookmarks

function mm_post_google ($curl, $vars)
{
  extract ($vars);
  $curl->setRedirect (false);
  $page = $curl->post ('https://www.google.com/accounts/ServiceLoginAuth', array ('continue' => 'http://www.google.com/bookmarks/', 'service' => 'bookmarks', 'nui' => '1', 'hl' => 'en', 'Email' => $username, 'Passwd' => $password, 'rmShown' => '1', 'null' => 'Sign in'));
  if ($err = $curl->getError ())
  {
    return $err;
  }
  if (302 != $curl->getHttpCode ())
  {
    return ASP_ERROR_LOGIN;
  }
  $page = $curl->get ('http://www.google.com/bookmarks/mark?op=add');
  if (!preg_match ('#href="(/bookmarks/mark[^"]+)"#i', $page, $match))
  {
    return ASP_ERROR_POST;
  }
  $submit_url = 'http://www.google.com'.str_replace ("&amp;", "&", $match[1]);
  $page = $curl->get ($submit_url);
  if (!preg_match ('#name=sig value="([^"]+)"#', $page, $match))
  {
    return ASP_ERROR_POST;
  }
  if (!preg_match ('#action="(/bookmarks/mark[^"]+)"#i', $page, $match2))
  {
    return ASP_ERROR_POST;
  }
  $submit_url = 'http://www.google.com'.str_replace ("&amp;", "&", $match2[1]);
  $sig = $match[1];
  $postdata = array(
    'bkmk'   => $link,
    'title'  => $title,
    'labels' => implode (',',
    $tags),
    'annotation' => $extended,
    'sig'        => $sig,
    'prev'       => '/lookup',
    'q'          => 'is:bookmarked',
    'btnA'       => 'Add bookmark',
  );
  $page = $curl->post ($submit_url, $postdata);
  foreach ($postdata as $key => $val)
  {
    $str .= "$key == $val \n";
  }
  if ($err = $curl->getError ())
  {
    return $err;
  }
  if (302 != $curl->getHttpCode ())
  {
    return ASP_ERROR_POST;
  }
  return ASP_ERROR_OK;
}

function mm_post_linkatopia ($curl, $vars)
{
  extract ($vars);
  $page = $curl->post ('http://linkatopia.com/login');
  $hiddens = mm_search ('type="hidden"', ">", $page);
  foreach ($hiddens as $hidden)
  {
    $hd_name            = mm_search ('name="', "\"", $hidden, false);
    $hd_name            = trim ($hd_name[0]);
    $hd_val             = mm_search ('value="', "\"", $hidden, false);
    $hd_val             = trim ($hd_val[0]);
    $postdata[$hd_name] = $hd_val;
  }
  $rrr = array_merge ($postdata, array ('a1' => $username, 'a2' => $password, 'submit' => 'Submit'));
  $page = $curl->post ('http://linkatopia.com/login', $rrr);
  if ($err = $curl->getError ())
  {
    return $err;
  }
  if (200 != $curl->getHttpCode ())
  {
    return ASP_ERROR_LOGIN;
  }
  if (substr_count ($page, 'User Name Not Found') || substr_count ($page, 'Incorrect Password'))
  {
    return ASP_ERROR_LOGIN;
  }
  $page = $curl->get ('http://linkatopia.com/add');
  if (!is_array ($tags) || empty ($tags))
  {
    $tags[] = substr ($title, 0, 15);
  }
  $postdata = array(
    'rateSelect' => '1',
    'xurl'       => $link,
    'xtitle'     => $title,
    'xtags'      => implode (',',
    $tags),
    'xnotes'   => $extended,
    'nbox1'    => '255 characters max',
    'zprivacy' => '0',
    'zdate'    => date ('Ymd',
    time ()),
    'zrating' => '0',
    'addlink' => 'Save',
  );
  $hiddens = mm_search ('type="hidden"', ">", $page);
  foreach ($hiddens as $hidden)
  {
    $hd_name            = mm_search ('name="', "\"", $hidden, false);
    $hd_name            = trim ($hd_name[0]);
    $hd_val             = mm_search ('value="', "\"", $hidden, false);
    $hd_val             = trim ($hd_val[0]);
    $postdata[$hd_name] = $hd_val;
  }
  if ($err = $curl->getError ())
  {
    return $err;
  }
  $page = $curl->post ('http://linkatopia.com/add', $postdata, 'http://linkatopia.com/add');
  if ($curl->getHttpCode () == 0)
  {
    return ASP_ERROR_PROXY;
  }
  if ($err = $curl->getError ())
  {
    return $err;
  }
  if ( (200 != $curl->getHttpCode ()))
  {
    return ASP_ERROR_POST;
  }
  return ASP_ERROR_OK;
}

function mm_post_sphinn ($curl, $vars)
{
  extract ($vars);
  $extra = trim ($extra);
  if (count ($tags) == 0)
  {
    $tags[] = $title;
  }
  $page = $curl->post ('http://sphinn.com/login', array ('action' => 'login', 'processlogin' => '1', 'return' => '', 'username' => $username, 'password' => $password, 'persistent' => '', '' => 'Login'));
  if (substr_count ($page, 'incorrect username or password'))
  {
    return ASP_ERROR_LOGIN;
  }
  if ($err = $curl->getError ())
  {
    return $err;
  }
  if (200 != $curl->getHttpCode ())
  {
    return ASP_ERROR_LOGIN;
  }
  $postdata = array(
    'url'   => $link,
    'title' => $title,
    'tags'  => implode (',',
    $tags),
    'bodytext'  => htmlentities (strip_tags ($extended)),
    'category'  => $extra,
    'submit'    => 'Submit News Story',
    'trackback' => '',
  );
  $page = $curl->get ('http://sphinn.com/submit');
  if ($curl->getHttpCode () == 0 || strpos ($page, "You have already submitted a post today"))
  {
    return ASP_ERROR_PROXY;
  }
  $hiddens = mm_search ('type="hidden"', ">", $page);
  foreach ($hiddens as $hidden)
  {
    $hd_name = mm_search ('name="', "\"", $hidden, false);
    $hd_name = trim ($hd_name[0]);
    $hd_val  = mm_search ('value="', "\"", $hidden, false);
    $hd_val  = trim ($hd_val[0]);
    if ($hd_name)
    {
      $found = true;
    }
    $postdata[$hd_name] = $hd_val;
  }
  if (!$found)
  {
    return ASP_ERROR_POST;
  }
  $postdata[phase]    = '1';
  $postdata[url]      = $link;
  $postdata[category] = $extra;
  $page               = $curl->post ('http://sphinn.com/submit', $postdata);
  if ($curl->getHttpCode () == 0)
  {
    return ASP_ERROR_PROXY;
  }
  $hiddens = mm_search ('type="hidden"', ">", $page);
  foreach ($hiddens as $hidden)
  {
    $hd_name            = mm_search ('name="', "\"", $hidden, false);
    $hd_name            = trim ($hd_name[0]);
    $hd_val             = mm_search ('value="', "\"", $hidden, false);
    $hd_val             = trim ($hd_val[0]);
    $postdata[$hd_name] = $hd_val;
  }
  $postdata[phase] = '2';
  $page = $curl->post ('http://sphinn.com/submit', $postdata);
  if ($curl->getHttpCode () == 0)
  {
    return ASP_ERROR_PROXY;
  }
  $postdata[phase] = '3';
  $page = $curl->post ('http://sphinn.com/submit', $postdata);
  if ($curl->getHttpCode () == 0)
  {
    return ASP_ERROR_PROXY;
  }
  if ($err = $curl->getError ())
  {
    return $err;
  }
  if (strpos ($page, 'incomplete title or text') || (200 != $curl->getHttpCode ()))
  {
    return ASP_ERROR_POST;
  }
  return ASP_ERROR_OK;
}

function mm_post_magnolia ($curl, $vars)
{
  extract ($vars);
  if (count ($tags) == 0)
  {
    $tags[] = substr ($title, 0, 39);
  }
  $page = $curl->post ('http://ma.gnolia.com/authentication/signin_dispatcher', array ('preferred_auth_provider[name]' => 'magnolia', 'username' => $username, 'password' => $password, 'auth_method_magnolia' => 'password'), 'http://ma.gnolia.com/signin');
  if ($err = $curl->getError ())
  {
    return $err;
  }
  $page = $curl->post ('http://ma.gnolia.com/bookmarks/create', array ('url' => $link, 'title' => $title, 'description' => $extended, 'tags' => implode (',', $tags), 'private' => '', 'rating' => 0), 'http://ma.gnolia.com/bookmarks/add');
  if ($err = $curl->getError ())
  {
    return $err;
  }
  if (302 == $curl->getHttpCode () || 200 == $curl->getHttpCode ())
  {
  }
  else
  {
    return ASP_ERROR_POST;
  }
  if (strpos ($curl->redirect_url, 'http://ma.gnolia.com/bookmarks/create') === 0)
  {
    return ASP_ERROR_POST;
  }
  return ASP_ERROR_OK;
}

function mm_post_linkroll ($curl, $vars)
{
  extract ($vars);
  if (count ($tags) == 0)
  {
    $tags[] = $title;
  }
  $page = $curl->post ('http://www.linkroll.com/index.php?action=authorize', array ('remember' => 1, 'user_login' => $username, 'user_password' => $password, 'track' => '', 'url' => '', 'title' => ''), 'http://www.linkroll.com/index.php?action=login');
  if (strpos ($page, 'Incorrect username or password') || (200 != $curl->getHttpCode ()))
  {
    return ASP_ERROR_LOGIN;
  }
  $page = $curl->get ('http://www.linkroll.com/index.php?action=addLink');
  $page = $curl->post ('http://www.linkroll.com/index.php?action=saveLink&id=', array ('user' => '', 'link_url' => $link, 'link_title' => $title, 'link_description' => $extended, 'link_categories' => implode (',', $tags), 'B3' => 'Save'), 'http://www.linkroll.com/index.php?action=addLink');
  if ($err = $curl->getError ())
  {
    return $err;
  }
  if (strpos ($page, 'url already exists') === 0 || (200 != $curl->getHttpCode ()))
  {
    return ASP_ERROR_POST;
  }
  return ASP_ERROR_OK;
}

function mm_search ($start, $end, $string, $borders = true)
{
  $reg = "!".preg_quote ($start)."(.*?)".preg_quote ($end)."!is";
  preg_match_all ($reg, $string, $matches);
  if ($borders)
  {
    return $matches[0];
  }
  else
  {
    return $matches[1];
  }
}
?> 