<?php
return array(
	'ctrl' => array(
		'label' => 'title',
		'tstamp' => 'tstamp',
		'default_sortby' => 'ORDER BY title',
		'title' => 'LLL:EXT:lang/locallang_tca.xlf:sys_language',
		'adminOnly' => 1,
		'rootLevel' => 1,
		'enablecolumns' => array(
			'disabled' => 'hidden'
		),
		'typeicon_column' => 'flag',
		'typeicon_classes' => array(
			'default' => 'mimetypes-x-sys_language',
			'mask' => 'flags-###TYPE###'
		),
		'versioningWS_alwaysAllowLiveEdit' => TRUE
	),
	'interface' => array(
		'showRecordFieldList' => 'hidden,title'
	),
	'columns' => array(
		'title' => array(
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.language',
			'config' => array(
				'type' => 'input',
				'size' => '35',
				'max' => '80',
				'eval' => 'trim,required'
			)
		),
		'hidden' => array(
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.disable',
			'exclude' => 1,
			'config' => array(
				'type' => 'check',
				'default' => '0'
			)
		),
		'language_isocode' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_language.language_isocode',
			'config' => array(
				'type' => 'select',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
				'items' => array(),
				'itemsProcFunc' => \TYPO3\CMS\Core\Service\IsoCodeService::class . '->renderIsoCodeSelectDropdown',
			)
		),
		'static_lang_isocode' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_language.isocode',
			'displayCond' => 'EXT:static_info_tables:LOADED:true',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('', 0)
				),
				'foreign_table' => 'static_languages',
				'foreign_table_where' => 'AND static_languages.pid=0 ORDER BY static_languages.lg_name_en',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1
			)
		),
		'flag' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_language.flag',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('', 0, ''),
					array('multiple', 'multiple', 'EXT:core/Resources/Public/Icons/Flags/multiple.png'),
					array('ad', 'ad', 'EXT:core/Resources/Public/Icons/Flags/ad.png'),
					array('ae', 'ae', 'EXT:core/Resources/Public/Icons/Flags/ae.png'),
					array('af', 'af', 'EXT:core/Resources/Public/Icons/Flags/af.png'),
					array('ag', 'ag', 'EXT:core/Resources/Public/Icons/Flags/ag.png'),
					array('ai', 'ai', 'EXT:core/Resources/Public/Icons/Flags/ai.png'),
					array('al', 'al', 'EXT:core/Resources/Public/Icons/Flags/al.png'),
					array('am', 'am', 'EXT:core/Resources/Public/Icons/Flags/am.png'),
					array('an', 'an', 'EXT:core/Resources/Public/Icons/Flags/an.png'),
					array('ao', 'ao', 'EXT:core/Resources/Public/Icons/Flags/ao.png'),
					array('ar', 'ar', 'EXT:core/Resources/Public/Icons/Flags/ar.png'),
					array('as', 'as', 'EXT:core/Resources/Public/Icons/Flags/as.png'),
					array('at', 'at', 'EXT:core/Resources/Public/Icons/Flags/at.png'),
					array('au', 'au', 'EXT:core/Resources/Public/Icons/Flags/au.png'),
					array('aw', 'aw', 'EXT:core/Resources/Public/Icons/Flags/aw.png'),
					array('ax', 'ax', 'EXT:core/Resources/Public/Icons/Flags/ax.png'),
					array('az', 'az', 'EXT:core/Resources/Public/Icons/Flags/az.png'),
					array('ba', 'ba', 'EXT:core/Resources/Public/Icons/Flags/ba.png'),
					array('bb', 'bb', 'EXT:core/Resources/Public/Icons/Flags/bb.png'),
					array('bd', 'bd', 'EXT:core/Resources/Public/Icons/Flags/bd.png'),
					array('be', 'be', 'EXT:core/Resources/Public/Icons/Flags/be.png'),
					array('bf', 'bf', 'EXT:core/Resources/Public/Icons/Flags/bf.png'),
					array('bg', 'bg', 'EXT:core/Resources/Public/Icons/Flags/bg.png'),
					array('bh', 'bh', 'EXT:core/Resources/Public/Icons/Flags/bh.png'),
					array('bi', 'bi', 'EXT:core/Resources/Public/Icons/Flags/bi.png'),
					array('bj', 'bj', 'EXT:core/Resources/Public/Icons/Flags/bj.png'),
					array('bm', 'bm', 'EXT:core/Resources/Public/Icons/Flags/bm.png'),
					array('bn', 'bn', 'EXT:core/Resources/Public/Icons/Flags/bn.png'),
					array('bo', 'bo', 'EXT:core/Resources/Public/Icons/Flags/bo.png'),
					array('br', 'br', 'EXT:core/Resources/Public/Icons/Flags/br.png'),
					array('bs', 'bs', 'EXT:core/Resources/Public/Icons/Flags/bs.png'),
					array('bt', 'bt', 'EXT:core/Resources/Public/Icons/Flags/bt.png'),
					array('bv', 'bv', 'EXT:core/Resources/Public/Icons/Flags/bv.png'),
					array('bw', 'bw', 'EXT:core/Resources/Public/Icons/Flags/bw.png'),
					array('by', 'by', 'EXT:core/Resources/Public/Icons/Flags/by.png'),
					array('bz', 'bz', 'EXT:core/Resources/Public/Icons/Flags/bz.png'),
					array('ca', 'ca', 'EXT:core/Resources/Public/Icons/Flags/ca.png'),
					array('catalonia', 'catalonia', 'EXT:core/Resources/Public/Icons/Flags/catalonia.png'),
					array('cc', 'cc', 'EXT:core/Resources/Public/Icons/Flags/cc.png'),
					array('cd', 'cd', 'EXT:core/Resources/Public/Icons/Flags/cd.png'),
					array('cf', 'cf', 'EXT:core/Resources/Public/Icons/Flags/cf.png'),
					array('cg', 'cg', 'EXT:core/Resources/Public/Icons/Flags/cg.png'),
					array('ch', 'ch', 'EXT:core/Resources/Public/Icons/Flags/ch.png'),
					array('ci', 'ci', 'EXT:core/Resources/Public/Icons/Flags/ci.png'),
					array('ck', 'ck', 'EXT:core/Resources/Public/Icons/Flags/ck.png'),
					array('cl', 'cl', 'EXT:core/Resources/Public/Icons/Flags/cl.png'),
					array('cm', 'cm', 'EXT:core/Resources/Public/Icons/Flags/cm.png'),
					array('cn', 'cn', 'EXT:core/Resources/Public/Icons/Flags/cn.png'),
					array('co', 'co', 'EXT:core/Resources/Public/Icons/Flags/co.png'),
					array('cr', 'cr', 'EXT:core/Resources/Public/Icons/Flags/cr.png'),
					array('cs', 'cs', 'EXT:core/Resources/Public/Icons/Flags/cs.png'),
					array('cu', 'cu', 'EXT:core/Resources/Public/Icons/Flags/cu.png'),
					array('cv', 'cv', 'EXT:core/Resources/Public/Icons/Flags/cv.png'),
					array('cx', 'cx', 'EXT:core/Resources/Public/Icons/Flags/cx.png'),
					array('cy', 'cy', 'EXT:core/Resources/Public/Icons/Flags/cy.png'),
					array('cz', 'cz', 'EXT:core/Resources/Public/Icons/Flags/cz.png'),
					array('de', 'de', 'EXT:core/Resources/Public/Icons/Flags/de.png'),
					array('dj', 'dj', 'EXT:core/Resources/Public/Icons/Flags/dj.png'),
					array('dk', 'dk', 'EXT:core/Resources/Public/Icons/Flags/dk.png'),
					array('dm', 'dm', 'EXT:core/Resources/Public/Icons/Flags/dm.png'),
					array('do', 'do', 'EXT:core/Resources/Public/Icons/Flags/do.png'),
					array('dz', 'dz', 'EXT:core/Resources/Public/Icons/Flags/dz.png'),
					array('ec', 'ec', 'EXT:core/Resources/Public/Icons/Flags/ec.png'),
					array('ee', 'ee', 'EXT:core/Resources/Public/Icons/Flags/ee.png'),
					array('eg', 'eg', 'EXT:core/Resources/Public/Icons/Flags/eg.png'),
					array('eh', 'eh', 'EXT:core/Resources/Public/Icons/Flags/eh.png'),
					array('england', 'england', 'EXT:core/Resources/Public/Icons/Flags/england.png'),
					array('er', 'er', 'EXT:core/Resources/Public/Icons/Flags/er.png'),
					array('es', 'es', 'EXT:core/Resources/Public/Icons/Flags/es.png'),
					array('et', 'et', 'EXT:core/Resources/Public/Icons/Flags/et.png'),
					array('europeanunion', 'europeanunion', 'EXT:core/Resources/Public/Icons/Flags/europeanunion.png'),
					array('fam', 'fam', 'EXT:core/Resources/Public/Icons/Flags/fam.png'),
					array('fi', 'fi', 'EXT:core/Resources/Public/Icons/Flags/fi.png'),
					array('fj', 'fj', 'EXT:core/Resources/Public/Icons/Flags/fj.png'),
					array('fk', 'fk', 'EXT:core/Resources/Public/Icons/Flags/fk.png'),
					array('fm', 'fm', 'EXT:core/Resources/Public/Icons/Flags/fm.png'),
					array('fo', 'fo', 'EXT:core/Resources/Public/Icons/Flags/fo.png'),
					array('fr', 'fr', 'EXT:core/Resources/Public/Icons/Flags/fr.png'),
					array('ga', 'ga', 'EXT:core/Resources/Public/Icons/Flags/ga.png'),
					array('gb', 'gb', 'EXT:core/Resources/Public/Icons/Flags/gb.png'),
					array('gd', 'gd', 'EXT:core/Resources/Public/Icons/Flags/gd.png'),
					array('ge', 'ge', 'EXT:core/Resources/Public/Icons/Flags/ge.png'),
					array('gf', 'gf', 'EXT:core/Resources/Public/Icons/Flags/gf.png'),
					array('gh', 'gh', 'EXT:core/Resources/Public/Icons/Flags/gh.png'),
					array('gi', 'gi', 'EXT:core/Resources/Public/Icons/Flags/gi.png'),
					array('gl', 'gl', 'EXT:core/Resources/Public/Icons/Flags/gl.png'),
					array('gm', 'gm', 'EXT:core/Resources/Public/Icons/Flags/gm.png'),
					array('gn', 'gn', 'EXT:core/Resources/Public/Icons/Flags/gn.png'),
					array('gp', 'gp', 'EXT:core/Resources/Public/Icons/Flags/gp.png'),
					array('gq', 'gq', 'EXT:core/Resources/Public/Icons/Flags/gq.png'),
					array('gr', 'gr', 'EXT:core/Resources/Public/Icons/Flags/gr.png'),
					array('gs', 'gs', 'EXT:core/Resources/Public/Icons/Flags/gs.png'),
					array('gt', 'gt', 'EXT:core/Resources/Public/Icons/Flags/gt.png'),
					array('gu', 'gu', 'EXT:core/Resources/Public/Icons/Flags/gu.png'),
					array('gw', 'gw', 'EXT:core/Resources/Public/Icons/Flags/gw.png'),
					array('gy', 'gy', 'EXT:core/Resources/Public/Icons/Flags/gy.png'),
					array('hk', 'hk', 'EXT:core/Resources/Public/Icons/Flags/hk.png'),
					array('hm', 'hm', 'EXT:core/Resources/Public/Icons/Flags/hm.png'),
					array('hn', 'hn', 'EXT:core/Resources/Public/Icons/Flags/hn.png'),
					array('hr', 'hr', 'EXT:core/Resources/Public/Icons/Flags/hr.png'),
					array('ht', 'ht', 'EXT:core/Resources/Public/Icons/Flags/ht.png'),
					array('hu', 'hu', 'EXT:core/Resources/Public/Icons/Flags/hu.png'),
					array('id', 'id', 'EXT:core/Resources/Public/Icons/Flags/id.png'),
					array('ie', 'ie', 'EXT:core/Resources/Public/Icons/Flags/ie.png'),
					array('il', 'il', 'EXT:core/Resources/Public/Icons/Flags/il.png'),
					array('in', 'in', 'EXT:core/Resources/Public/Icons/Flags/in.png'),
					array('io', 'io', 'EXT:core/Resources/Public/Icons/Flags/io.png'),
					array('iq', 'iq', 'EXT:core/Resources/Public/Icons/Flags/iq.png'),
					array('ir', 'ir', 'EXT:core/Resources/Public/Icons/Flags/ir.png'),
					array('is', 'is', 'EXT:core/Resources/Public/Icons/Flags/is.png'),
					array('it', 'it', 'EXT:core/Resources/Public/Icons/Flags/it.png'),
					array('jm', 'jm', 'EXT:core/Resources/Public/Icons/Flags/jm.png'),
					array('jo', 'jo', 'EXT:core/Resources/Public/Icons/Flags/jo.png'),
					array('jp', 'jp', 'EXT:core/Resources/Public/Icons/Flags/jp.png'),
					array('ke', 'ke', 'EXT:core/Resources/Public/Icons/Flags/ke.png'),
					array('kg', 'kg', 'EXT:core/Resources/Public/Icons/Flags/kg.png'),
					array('kh', 'kh', 'EXT:core/Resources/Public/Icons/Flags/kh.png'),
					array('ki', 'ki', 'EXT:core/Resources/Public/Icons/Flags/ki.png'),
					array('km', 'km', 'EXT:core/Resources/Public/Icons/Flags/km.png'),
					array('kn', 'kn', 'EXT:core/Resources/Public/Icons/Flags/kn.png'),
					array('kp', 'kp', 'EXT:core/Resources/Public/Icons/Flags/kp.png'),
					array('kr', 'kr', 'EXT:core/Resources/Public/Icons/Flags/kr.png'),
					array('kw', 'kw', 'EXT:core/Resources/Public/Icons/Flags/kw.png'),
					array('ky', 'ky', 'EXT:core/Resources/Public/Icons/Flags/ky.png'),
					array('kz', 'kz', 'EXT:core/Resources/Public/Icons/Flags/kz.png'),
					array('la', 'la', 'EXT:core/Resources/Public/Icons/Flags/la.png'),
					array('lb', 'lb', 'EXT:core/Resources/Public/Icons/Flags/lb.png'),
					array('lc', 'lc', 'EXT:core/Resources/Public/Icons/Flags/lc.png'),
					array('li', 'li', 'EXT:core/Resources/Public/Icons/Flags/li.png'),
					array('lk', 'lk', 'EXT:core/Resources/Public/Icons/Flags/lk.png'),
					array('lr', 'lr', 'EXT:core/Resources/Public/Icons/Flags/lr.png'),
					array('ls', 'ls', 'EXT:core/Resources/Public/Icons/Flags/ls.png'),
					array('lt', 'lt', 'EXT:core/Resources/Public/Icons/Flags/lt.png'),
					array('lu', 'lu', 'EXT:core/Resources/Public/Icons/Flags/lu.png'),
					array('lv', 'lv', 'EXT:core/Resources/Public/Icons/Flags/lv.png'),
					array('ly', 'ly', 'EXT:core/Resources/Public/Icons/Flags/ly.png'),
					array('ma', 'ma', 'EXT:core/Resources/Public/Icons/Flags/ma.png'),
					array('mc', 'mc', 'EXT:core/Resources/Public/Icons/Flags/mc.png'),
					array('md', 'md', 'EXT:core/Resources/Public/Icons/Flags/md.png'),
					array('me', 'me', 'EXT:core/Resources/Public/Icons/Flags/me.png'),
					array('mg', 'mg', 'EXT:core/Resources/Public/Icons/Flags/mg.png'),
					array('mh', 'mh', 'EXT:core/Resources/Public/Icons/Flags/mh.png'),
					array('mk', 'mk', 'EXT:core/Resources/Public/Icons/Flags/mk.png'),
					array('ml', 'ml', 'EXT:core/Resources/Public/Icons/Flags/ml.png'),
					array('mm', 'mm', 'EXT:core/Resources/Public/Icons/Flags/mm.png'),
					array('mn', 'mn', 'EXT:core/Resources/Public/Icons/Flags/mn.png'),
					array('mo', 'mo', 'EXT:core/Resources/Public/Icons/Flags/mo.png'),
					array('mp', 'mp', 'EXT:core/Resources/Public/Icons/Flags/mp.png'),
					array('mq', 'mq', 'EXT:core/Resources/Public/Icons/Flags/mq.png'),
					array('mr', 'mr', 'EXT:core/Resources/Public/Icons/Flags/mr.png'),
					array('ms', 'ms', 'EXT:core/Resources/Public/Icons/Flags/ms.png'),
					array('mt', 'mt', 'EXT:core/Resources/Public/Icons/Flags/mt.png'),
					array('mu', 'mu', 'EXT:core/Resources/Public/Icons/Flags/mu.png'),
					array('mv', 'mv', 'EXT:core/Resources/Public/Icons/Flags/mv.png'),
					array('mw', 'mw', 'EXT:core/Resources/Public/Icons/Flags/mw.png'),
					array('mx', 'mx', 'EXT:core/Resources/Public/Icons/Flags/mx.png'),
					array('my', 'my', 'EXT:core/Resources/Public/Icons/Flags/my.png'),
					array('mz', 'mz', 'EXT:core/Resources/Public/Icons/Flags/mz.png'),
					array('na', 'na', 'EXT:core/Resources/Public/Icons/Flags/na.png'),
					array('nc', 'nc', 'EXT:core/Resources/Public/Icons/Flags/nc.png'),
					array('ne', 'ne', 'EXT:core/Resources/Public/Icons/Flags/ne.png'),
					array('nf', 'nf', 'EXT:core/Resources/Public/Icons/Flags/nf.png'),
					array('ng', 'ng', 'EXT:core/Resources/Public/Icons/Flags/ng.png'),
					array('ni', 'ni', 'EXT:core/Resources/Public/Icons/Flags/ni.png'),
					array('nl', 'nl', 'EXT:core/Resources/Public/Icons/Flags/nl.png'),
					array('no', 'no', 'EXT:core/Resources/Public/Icons/Flags/no.png'),
					array('np', 'np', 'EXT:core/Resources/Public/Icons/Flags/np.png'),
					array('nr', 'nr', 'EXT:core/Resources/Public/Icons/Flags/nr.png'),
					array('nu', 'nu', 'EXT:core/Resources/Public/Icons/Flags/nu.png'),
					array('nz', 'nz', 'EXT:core/Resources/Public/Icons/Flags/nz.png'),
					array('om', 'om', 'EXT:core/Resources/Public/Icons/Flags/om.png'),
					array('pa', 'pa', 'EXT:core/Resources/Public/Icons/Flags/pa.png'),
					array('pe', 'pe', 'EXT:core/Resources/Public/Icons/Flags/pe.png'),
					array('pf', 'pf', 'EXT:core/Resources/Public/Icons/Flags/pf.png'),
					array('pg', 'pg', 'EXT:core/Resources/Public/Icons/Flags/pg.png'),
					array('ph', 'ph', 'EXT:core/Resources/Public/Icons/Flags/ph.png'),
					array('pk', 'pk', 'EXT:core/Resources/Public/Icons/Flags/pk.png'),
					array('pl', 'pl', 'EXT:core/Resources/Public/Icons/Flags/pl.png'),
					array('pm', 'pm', 'EXT:core/Resources/Public/Icons/Flags/pm.png'),
					array('pn', 'pn', 'EXT:core/Resources/Public/Icons/Flags/pn.png'),
					array('pr', 'pr', 'EXT:core/Resources/Public/Icons/Flags/pr.png'),
					array('ps', 'ps', 'EXT:core/Resources/Public/Icons/Flags/ps.png'),
					array('pt', 'pt', 'EXT:core/Resources/Public/Icons/Flags/pt.png'),
					array('pw', 'pw', 'EXT:core/Resources/Public/Icons/Flags/pw.png'),
					array('py', 'py', 'EXT:core/Resources/Public/Icons/Flags/py.png'),
					array('qa', 'qa', 'EXT:core/Resources/Public/Icons/Flags/qa.png'),
					array('qc', 'qc', 'EXT:core/Resources/Public/Icons/Flags/qc.png'),
					array('re', 're', 'EXT:core/Resources/Public/Icons/Flags/re.png'),
					array('ro', 'ro', 'EXT:core/Resources/Public/Icons/Flags/ro.png'),
					array('rs', 'rs', 'EXT:core/Resources/Public/Icons/Flags/rs.png'),
					array('ru', 'ru', 'EXT:core/Resources/Public/Icons/Flags/ru.png'),
					array('rw', 'rw', 'EXT:core/Resources/Public/Icons/Flags/rw.png'),
					array('sa', 'sa', 'EXT:core/Resources/Public/Icons/Flags/sa.png'),
					array('sb', 'sb', 'EXT:core/Resources/Public/Icons/Flags/sb.png'),
					array('sc', 'sc', 'EXT:core/Resources/Public/Icons/Flags/sc.png'),
					array('scotland', 'scotland', 'EXT:core/Resources/Public/Icons/Flags/scotland.png'),
					array('sd', 'sd', 'EXT:core/Resources/Public/Icons/Flags/sd.png'),
					array('se', 'se', 'EXT:core/Resources/Public/Icons/Flags/se.png'),
					array('sg', 'sg', 'EXT:core/Resources/Public/Icons/Flags/sg.png'),
					array('sh', 'sh', 'EXT:core/Resources/Public/Icons/Flags/sh.png'),
					array('si', 'si', 'EXT:core/Resources/Public/Icons/Flags/si.png'),
					array('sj', 'sj', 'EXT:core/Resources/Public/Icons/Flags/sj.png'),
					array('sk', 'sk', 'EXT:core/Resources/Public/Icons/Flags/sk.png'),
					array('sl', 'sl', 'EXT:core/Resources/Public/Icons/Flags/sl.png'),
					array('sm', 'sm', 'EXT:core/Resources/Public/Icons/Flags/sm.png'),
					array('sn', 'sn', 'EXT:core/Resources/Public/Icons/Flags/sn.png'),
					array('so', 'so', 'EXT:core/Resources/Public/Icons/Flags/so.png'),
					array('sr', 'sr', 'EXT:core/Resources/Public/Icons/Flags/sr.png'),
					array('st', 'st', 'EXT:core/Resources/Public/Icons/Flags/st.png'),
					array('sv', 'sv', 'EXT:core/Resources/Public/Icons/Flags/sv.png'),
					array('sy', 'sy', 'EXT:core/Resources/Public/Icons/Flags/sy.png'),
					array('sz', 'sz', 'EXT:core/Resources/Public/Icons/Flags/sz.png'),
					array('tc', 'tc', 'EXT:core/Resources/Public/Icons/Flags/tc.png'),
					array('td', 'td', 'EXT:core/Resources/Public/Icons/Flags/td.png'),
					array('tf', 'tf', 'EXT:core/Resources/Public/Icons/Flags/tf.png'),
					array('tg', 'tg', 'EXT:core/Resources/Public/Icons/Flags/tg.png'),
					array('th', 'th', 'EXT:core/Resources/Public/Icons/Flags/th.png'),
					array('tj', 'tj', 'EXT:core/Resources/Public/Icons/Flags/tj.png'),
					array('tk', 'tk', 'EXT:core/Resources/Public/Icons/Flags/tk.png'),
					array('tl', 'tl', 'EXT:core/Resources/Public/Icons/Flags/tl.png'),
					array('tm', 'tm', 'EXT:core/Resources/Public/Icons/Flags/tm.png'),
					array('tn', 'tn', 'EXT:core/Resources/Public/Icons/Flags/tn.png'),
					array('to', 'to', 'EXT:core/Resources/Public/Icons/Flags/to.png'),
					array('tr', 'tr', 'EXT:core/Resources/Public/Icons/Flags/tr.png'),
					array('tt', 'tt', 'EXT:core/Resources/Public/Icons/Flags/tt.png'),
					array('tv', 'tv', 'EXT:core/Resources/Public/Icons/Flags/tv.png'),
					array('tw', 'tw', 'EXT:core/Resources/Public/Icons/Flags/tw.png'),
					array('tz', 'tz', 'EXT:core/Resources/Public/Icons/Flags/tz.png'),
					array('ua', 'ua', 'EXT:core/Resources/Public/Icons/Flags/ua.png'),
					array('ug', 'ug', 'EXT:core/Resources/Public/Icons/Flags/ug.png'),
					array('um', 'um', 'EXT:core/Resources/Public/Icons/Flags/um.png'),
					array('us', 'us', 'EXT:core/Resources/Public/Icons/Flags/us.png'),
					array('uy', 'uy', 'EXT:core/Resources/Public/Icons/Flags/uy.png'),
					array('uz', 'uz', 'EXT:core/Resources/Public/Icons/Flags/uz.png'),
					array('va', 'va', 'EXT:core/Resources/Public/Icons/Flags/va.png'),
					array('vc', 'vc', 'EXT:core/Resources/Public/Icons/Flags/vc.png'),
					array('ve', 've', 'EXT:core/Resources/Public/Icons/Flags/ve.png'),
					array('vg', 'vg', 'EXT:core/Resources/Public/Icons/Flags/vg.png'),
					array('vi', 'vi', 'EXT:core/Resources/Public/Icons/Flags/vi.png'),
					array('vn', 'vn', 'EXT:core/Resources/Public/Icons/Flags/vn.png'),
					array('vu', 'vu', 'EXT:core/Resources/Public/Icons/Flags/vu.png'),
					array('wales', 'wales', 'EXT:core/Resources/Public/Icons/Flags/wales.png'),
					array('wf', 'wf', 'EXT:core/Resources/Public/Icons/Flags/wf.png'),
					array('ws', 'ws', 'EXT:core/Resources/Public/Icons/Flags/ws.png'),
					array('ye', 'ye', 'EXT:core/Resources/Public/Icons/Flags/ye.png'),
					array('yt', 'yt', 'EXT:core/Resources/Public/Icons/Flags/yt.png'),
					array('za', 'za', 'EXT:core/Resources/Public/Icons/Flags/za.png'),
					array('zm', 'zm', 'EXT:core/Resources/Public/Icons/Flags/zm.png'),
					array('zw', 'zw', 'EXT:core/Resources/Public/Icons/Flags/zw.png')
				),
				'selicon_cols' => 16,
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1
			)
		)
	),
	'types' => array(
		'1' => array('showitem' => '--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.general;general,
										title,language_isocode,flag,
									--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access,
										hidden')
	)
);
