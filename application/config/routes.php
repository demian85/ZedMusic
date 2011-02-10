<?php
/**
 * ZedPlan OpenCorePHP Framework
 *
 * Copyright (c) 2005-2010, ZedPlan (http://www.zedplan.com)
 *
 *
 *
 * LICENSE
 *
 * This source file is subject to the GPL license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opencorephp.zedplan.com/license.txt
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to opencorephp@zedplan.com so we can send you a copy immediately.
 *
 * @copyright	Copyright (c) 2005-2010, ZedPlan (http://www.zedplan.com)
 * @link	http://opencorephp.zedplan.com
 * @license	http://opencorephp.zedplan.com/license.txt     GPL License
 */


return array(
/**
 * Specifies the first route parameter index that will be used for routing.
 * Aliases are resolved before obtaining the parameters.
 * If {routes.language_redirect} is 'param', the language code is excluded.
 * Eg: if requested URL is http://blog.mysite.com/admin/panel and start_index is 1, "admin" will be ignored.
 */
'start_index'	=> 0,

/**
 * Redirect based on client's language or detect language from the URL.
 * This option implicitly enables locale autodetection, even if {core.autodetect_locale} is FALSE.
 * 
 * Possible values:
 * 'subdomain' : detect language from subdomain. It will be the first label before the domain
 * 					Eg: if URL is http://blog.es.mysite.com, language will be "es"
 * 'param' : detect language from first indexed parameter. 
 * 					Eg: if URL is http://www.mysite.com/es/admin/panel, language will be "es"
 * NULL : disable this feature
 */
'language_redirect'		=> 'param',

/**
 * Route aliases. Keys are regular expressions (including delimiters) and values are replacements.
 * The regular expressions will be tested against requested route (the URI formed by the requested indexed params, excluding GET and named params)
 * When a replacement is done, the following aliases will be discarded.
 * 
 * Eg: #es/(.*)# => admin/panel/$1
 * When the requested route is "es/user/list", it will be translated as "admin/panel/user/list".
 * Route map is applied on the final route.
 */
'aliases'		=> array(),

/**
 * Route map. Allows creating aliases for modules, controllers and actions.
 * Keys are original names and values are aliases. 
 * Eg: users => usuarios : creates an alias for module "users" and allows an URL like http://www.hi.com/usuarios
 */
'route_map'		=> array(
					
				),

/**
 * Maps subdomains to modules. Keys are subdomain labels separated by dots and values are module names.
 * Eg:
 * admin.blog => blog/admin
 */
'subdomain_map'	=> array()
);
?>