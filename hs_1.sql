SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

CREATE DATABASE IF NOT EXISTS `hs_1` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
USE `hs_1`;

CREATE TABLE `account` (
  `account_id` int(12) NOT NULL,
  `subject` int(4) NOT NULL,
  `cost` int(8) DEFAULT NULL,
  `income` int(8) DEFAULT NULL,
  `sale_detail_id` int(12) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `bank` (
  `bank_id` int(10) NOT NULL,
  `bank_name` varchar(32) NOT NULL,
  `balance` decimal(12,2) NOT NULL DEFAULT '0.00',
  `weight` tinyint(3) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `bank` (`bank_id`, `bank_name`, `balance`, `weight`) VALUES
(6, 'b1', '-19493.00', 1),
(7, '农业银行', '-850.00', 0);

CREATE TABLE `bdetail` (
  `bdetail` int(18) NOT NULL,
  `bank_id` int(10) NOT NULL,
  `sum` decimal(12,2) NOT NULL,
  `balance` decimal(12,2) NOT NULL DEFAULT '0.00',
  `time` datetime NOT NULL,
  `summary` varchar(64) NOT NULL,
  `user` varchar(16) NOT NULL,
  `mytable` varchar(16) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
DELIMITER $$
CREATE TRIGGER `bdetail_before_insert` BEFORE INSERT ON `bdetail` FOR EACH ROW BEGIN
UPDATE bank
SET `bank`.`balance`= `bank`.`balance`+new.sum
WHERE `bank`.`bank_id`=new.bank_id;
END
$$
DELIMITER ;

CREATE TABLE `cat` (
  `cat_id` int(10) NOT NULL,
  `cat_name` varchar(32) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `cat` (`cat_id`, `cat_name`) VALUES
(1, '海鲜贝类');

CREATE TABLE `goods` (
  `goods_id` int(10) NOT NULL,
  `goods_name` varchar(32) NOT NULL,
  `goods_sn` varchar(32) NOT NULL,
  `cat_id` int(10) NOT NULL,
  `warn_stock` int(10) NOT NULL DEFAULT '0',
  `unit_id` int(6) NOT NULL,
  `out_price` decimal(8,1) NOT NULL DEFAULT '0.0',
  `promote` tinyint(1) NOT NULL DEFAULT '1',
  `is_show` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `goods` (`goods_id`, `goods_name`, `goods_sn`, `cat_id`, `warn_stock`, `unit_id`, `out_price`, `promote`, `is_show`) VALUES
(1, '大扇贝', 'dsb', 1, 10, 1, '20.0', 1, 1);

CREATE TABLE `info` (
  `id` int(1) NOT NULL,
  `cname` varchar(32) NOT NULL,
  `ctel` varchar(32) NOT NULL,
  `cadd` varchar(32) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='公司信息';

INSERT INTO `info` (`id`, `cname`, `ctel`, `cadd`) VALUES
(1, '2', '15183022666', 'sichuan');

CREATE TABLE `log` (
  `log_id` int(10) NOT NULL,
  `user` varchar(32) NOT NULL,
  `time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `remark` varchar(40) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `log` (`log_id`, `user`, `time`, `remark`) VALUES
(1, 'admin', '2017-08-18 09:31:40', '新建种类:海鲜贝类'),
(2, 'admin', '2017-08-18 09:32:22', '修改产品成功,old data:添加新产品扇贝'),
(3, 'admin', '2017-08-18 10:29:30', '修改产品成功,old data:1|扇贝|sb|1|10|斤|20.0|1|1'),
(4, 'admin', '2017-08-18 10:31:00', '修改产品成功,old data:1|大扇贝|sb|1|10|斤|20.0|1|1'),
(5, 'admin', '2017-08-18 10:35:14', '修改产品成功,old data:1|小扇贝|sb|1|10|斤|20.0|1|1');

CREATE TABLE `member` (
  `member_id` int(10) NOT NULL,
  `member_name` varchar(32) NOT NULL COMMENT '真实名字',
  `member_phone` varchar(16) DEFAULT NULL COMMENT '座机',
  `vip` float NOT NULL DEFAULT '1' COMMENT '最后购物时间',
  `member_sn` varchar(45) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='会员信息表';

INSERT INTO `member` (`member_id`, `member_name`, `member_phone`, `vip`, `member_sn`) VALUES
(2, '合江董三', '00', 1, 'hjds'),
(3, '红星牛儿', '', 1, 'hxne'),
(4, '合江税三', '', 1, 'hjss'),
(5, '叙永何勇', '', 0.95, 'xyhy'),
(6, '市场邱杰', '', 1, 'scqj'),
(7, '赤水王小华', '', 1, 'cswxh'),
(8, '习水廖庆', '13984255114', 0.95, 'xslq'),
(9, '泸州戴春', '13154601389', 1, 'lzdc'),
(10, '江门李二', '', 1, 'jmle'),
(11, '上马刘五', '', 1, 'smlw'),
(12, '合江刘五', '', 1, 'hjlw'),
(13, '合江张三姐', '', 1, 'hjzsj'),
(14, '合江宋七', '', 1, 'hjsq'),
(15, '赤水向大', '', 1, 'csxd'),
(16, '赤水乌大', '', 1, 'cswd'),
(17, '合江李八姐', '', 1, 'hjlbj'),
(18, '大洲驿小曾大', '', 1, 'dzyxzd'),
(19, '大洲驿屈六', '', 1, 'dzyql'),
(20, '上马王三', '', 1, 'smws'),
(21, '打古孙三', '', 1, 'dgss'),
(22, '大洲驿曾二', '', 1, 'dzyze'),
(23, '李华昌', '', 1, 'lhc'),
(24, '红星袁六嫂', '', 1, 'hxyls'),
(25, '红星小欧', '', 1, 'hxxo'),
(26, '红星屈七', '', 1, 'hxqq'),
(27, '红星眼镜刘波', '', 1, 'hxyjlb'),
(28, '红星郭三李二妹', '', 1, 'hxgslem'),
(29, '苏大姐刘波', '', 1, 'sdjlb'),
(30, '红星男赵三', '', 1, 'hxnzs'),
(31, '红星罗三江', '', 1, 'hxlsj'),
(32, '红星王七', '', 1, 'hxwq'),
(33, '付现客户', '', 1, 'fxkf'),
(34, '市场小秋', '', 1, 'scxq'),
(35, '市场刘二', '', 1, 'scle'),
(36, '市场马七', '', 1, 'scmq'),
(37, '市场罗四嫂', '', 1, 'sclss'),
(38, '市场周四', '', 1, 'sczs'),
(39, '市场马老幺', '', 1, 'scmly'),
(40, '市场王大1573', '', 1, 'scwd'),
(41, '韩卫', '', 1, 'hw'),
(42, '市场徐二', '', 1, 'scxe'),
(43, '市场邱三', '', 1, 'scqs'),
(44, '杨胖娃', '', 1, 'ypw'),
(45, '市场王二嫂', '', 1, 'scwes'),
(47, '红星王刚', '', 1, 'hxwg'),
(48, '石马沟小陈', '', 1, 'smgxc'),
(49, '石马沟安辉', '', 1, 'smgah'),
(50, '石马沟姜六', '', 1, 'smgjl'),
(51, '圣老幺', '', 1, 'sly'),
(52, '胥七', '', 1, 'xq'),
(53, '赤水李大毛', '', 1, 'csldm'),
(54, '通滩永红', '', 1, 'ttyh'),
(55, '通滩张六', '', 1, 'ttzl'),
(56, '罗庭红', '', 1, 'lth'),
(57, '乐山孙小洪', '', 1, 'lssxh'),
(58, '蒋四', '', 1, 'js'),
(59, '严老表', '', 1, 'ylb'),
(60, '孙家兵', '', 1, 'sjb'),
(61, '夏九', '', 1, 'xj'),
(62, '红星余小平', '', 1, 'hxyxp'),
(63, '河坝张三', '', 1, 'hbzs'),
(64, '摩托赵五', '', 1, 'mtzw'),
(65, '摩托吴帮举', '', 1, 'mtwbj'),
(66, '摩托李三', '', 1, 'mtls'),
(67, '泸州徐英', '', 1, 'lzxy'),
(68, '红星杨明', '', 1, 'hxym'),
(69, '福集刘国平', '', 1, 'fjlgp'),
(70, '福集乌三', '', 1, 'fjws'),
(71, '福集邬得超', '', 1, 'fjwdc'),
(72, '福集先大全', '', 1, 'fjxdq'),
(73, '福集邬二娃', '', 1, 'fjwew'),
(74, '福集罗四', '', 1, 'fjls'),
(75, '习水尹洪', '', 1, 'xsyh'),
(76, '尹小平', '', 1, 'yxp'),
(77, '春狗', '', 1, 'cg'),
(78, '赤水杨勇', '', 1, 'csyd'),
(79, '唐老幺', '', 1, 'tly'),
(80, '江安姜自华', '', 1, 'jajzh'),
(81, '叙永钟六', '', 1, 'xyzl'),
(82, '泸州钟七', '', 1, 'lzzq'),
(83, '时代商都张大姐', '', 1, 'sdsdzdj'),
(84, '李华绿', '', 1, 'lhl'),
(85, '泸州叶五', '', 1, 'lzyw'),
(86, '泸州余伟', '', 1, 'lzyw'),
(87, '小苟二', '', 1, 'xge'),
(88, '太福税六', '', 1, 'tfsl'),
(89, '太福杨四', '', 1, 'tfys'),
(90, '兆雅罗大嫂', '', 1, 'zylds'),
(91, '兆雅谢八', '', 1, 'zyxb'),
(92, '何志刚', '', 1, 'hzg'),
(93, '海鲜李六嫂', '', 1, 'hxlls'),
(94, '吴二罗三', '', 1, 'wels'),
(95, '肖刚', '', 1, 'xg'),
(96, '兴文何木匠', '', 1, 'xwhmj'),
(97, '兴文王彬', '', 1, 'xwwb'),
(98, '兴文曾松', '', 1, 'xwzs'),
(99, '游小飞', '151', 1, 'yxf'),
(100, '罗五哥', '', 1, 'lwg'),
(101, '秦四', '', 1, 'qs'),
(102, '兴文任光云', '', 1, 'xwrgy'),
(103, '史海飞', '', 1, 'shf'),
(104, '市场蒲正云', '', 1, 'scpzy'),
(105, '红星李二男', '', 1, 'hxle'),
(106, '纳溪万四', '', 1, 'nxws'),
(107, '纳溪曹四', '', 1, 'nxcs'),
(108, '纳溪万大万云', '', 1, 'nxwd'),
(109, '纳溪周五', '', 1, 'nxzw'),
(110, '纳溪廖策友', '', 1, 'nxlcy'),
(111, '纳溪万五', '', 1, 'nxww'),
(112, '合江董四', '', 1, 'hjds'),
(113, '合江温老幺', '', 1, 'hjwly'),
(114, '合江曾大', '', 1, 'hjzd'),
(115, '富顺大脚板', '', 1, 'fsdjb'),
(116, '富顺彭大', '', 1, 'fspd'),
(117, '富顺小波', '', 1, 'fsxb'),
(118, '隆昌小波1573', '', 1, 'lcxb'),
(119, '潘老幺', '', 1, 'ply'),
(120, '江安张四', '', 1, 'jazs'),
(121, '威信范义', '', 1, 'wxfy'),
(122, '威信王中海', '', 1, 'wxwzh'),
(123, '市场伍郎帮', '', 1, 'scwlb'),
(124, '朱沱戴伍', '', 1, 'ztdw'),
(125, '红星赵老幺', '', 1, 'hxzly'),
(126, '红星陈大张二', '', 1, 'hxcdze'),
(127, '红星余二幺舅', '', 1, 'hxyeyj'),
(128, '邱华军', '', 1, 'qhj'),
(129, '市场代春', '', 1, 'scdc'),
(130, '红星大王刚', '', 1, 'hxdwg'),
(131, '纳溪王天富', '', 1, 'nxwtf'),
(132, '何兵', '', 1, 'hb'),
(133, '况二', '', 1, 'ke'),
(134, '大城李四', '', 1, 'dcls'),
(135, '泸州石叶', '', 1, 'lzsy'),
(136, '玄滩吴大姐', '', 1, 'xtwdj'),
(137, '赤水小江', '', 1, 'csxj'),
(138, '红星邱四', '', 1, 'hxqs'),
(139, '习水刘建波', '', 1, 'xsljb'),
(140, '泸州小许', '', 1, 'lzxx'),
(141, '红星刘大', '', 1, 'hxld'),
(142, '兴文沈林', '', 1, 'xwsl'),
(143, '叙永邱二娃', '', 1, 'xyqew'),
(144, '高万', '', 1, 'gw'),
(145, '谢帮清', '', 1, 'xbq'),
(146, '市场潘三', '', 1, 'scps'),
(147, '曾科', '', 1, 'zk'),
(148, '车静', '', 1, 'cj'),
(149, '重湾何四', '', 1, 'zwhs'),
(150, '特兴胡六', '', 1, 'txhl'),
(151, '汇泓水产', '', 1, 'HHSC'),
(152, '尧大', '', 1, 'yd'),
(153, '三道拐唐麻口', '', 1, 'sdgtmk'),
(154, '李启达', '', 1, 'lqd'),
(155, '毕节小罗', '', 1, 'bjxl'),
(156, '泸州小陶', '', 1, 'lzxt'),
(157, '立石蒋大', '', 1, 'lsjd'),
(158, '榕山赵大平', '', 1, 'rszdp'),
(159, '长山杨六', '', 1, 'csyl'),
(160, '詹二', '', 1, 'ze'),
(161, '泰安小黄', '', 1, 'taxh'),
(162, '市场殷三', '', 1, 'scys'),
(163, '孟五', '', 1, 'mw'),
(164, '郑老幺', '', 1, 'zly'),
(165, '南城王四', '', 1, 'ncws'),
(166, '胡老么', '', 1, 'hly'),
(167, '邱友军', '', 1, 'qyj'),
(168, '润泽春天陈二', '', 1, 'rzctce'),
(169, '市场小王', '', 1, 'scxw'),
(170, '韩四李五姐', '', 1, 'hslwj'),
(171, '市场母乌鱼', '', 1, 'scmwy'),
(172, '段大', '', 1, 'dd'),
(173, '汇通舒忠', '', 1, 'htsz'),
(174, '胡市18783088780', '', 1, 'hs'),
(175, '土城李八', '', 1, 'tclb'),
(176, '红星黄大', '', 1, 'hxhd'),
(177, '长滩陈二', '', 1, 'ctce'),
(178, '彭江', '13350177577', 1, 'pj'),
(179, '纳溪向平', '', 1, 'nxxp'),
(180, '邱宗胜', '', 1, 'qzs'),
(181, '红星兰二', '', 1, 'hxle'),
(182, '通滩吉老幺', '', 1, 'TTJLY'),
(183, '郭俊星', '', 1, 'gjx'),
(184, '赵生群', '', 1, 'zsq'),
(185, '永寿郑老幺', '', 1, 'yszly'),
(186, '福集胡三', '', 1, 'fjhs'),
(188, '李波', '15884140758', 1, 'lb'),
(189, '福集周五嫂', '', 1, 'fjzws'),
(190, '纳溪徐四', '', 1, 'nxxs'),
(191, '陈平', '', 1, 'cp'),
(192, '魏二娃', '', 1, 'wew'),
(193, '大杨海鲜张鹏', '', 1, 'dyhxzp'),
(194, '罗氏水产批发', '', 1, 'lsscpf'),
(195, '肖天兵', '', 1, 'xtb'),
(196, '特兴老邓', '', 1, 'txld'),
(197, '南城小牟', '', 1, 'ncxm'),
(198, '13990948713', '', 1, '13990948713'),
(199, '红星老吴', '', 1, 'hxlw'),
(200, '金龙周六', '', 1, 'jlzl'),
(201, '况场范方军', '', 1, 'hcffj'),
(202, '马三姐', '', 1, 'MSJ'),
(203, '小罗三', '', 1, 'xls'),
(204, '泸州重白', '', 1, 'cb'),
(205, '慧才', '', 1, 'hc'),
(206, '国泰新城李七', '', 1, 'gtxclq'),
(207, '许胥强', '', 1, 'xxq'),
(208, '李林川EDY976', '', 1, 'll'),
(209, '江安老胡', '', 1, 'jalh'),
(210, '小孔女婿', '', 1, 'xknx'),
(211, '范方均况场腾飞水产', '18982740911', 1, 'ffj'),
(212, '市场刘七新彬', '', 1, 'sclqxb'),
(213, '胡市小辉', '', 1, 'HSXH'),
(214, '市场芶二', '', 1, 'scge'),
(215, '玄滩刘利友', '', 1, 'xtlly'),
(216, '诚信水产', '', 1, 'cxsc'),
(217, '罗明怀', '', 1, 'lmh'),
(218, '市场彭二', '', 1, 'SCPE'),
(219, '渔尚煌河鲜', '', 1, 'yshhx'),
(220, '兰田李七', '', 1, 'ltlq'),
(221, '合江保安', '', 1, 'HJBA'),
(222, '王清友', '', 1, 'wqy'),
(223, '18166437689小代', '', 1, '18166437689xd'),
(224, '镇雄杨国彬', '15126683658', 1, 'ZXYGB'),
(225, '市场放水老张', '', 1, 'SCFSLZ'),
(226, '小苹果小余', '', 1, 'xpgxy'),
(227, '郎学勇', '15750184404', 1, 'LXY'),
(228, '徐时义', '', 1, 'xsy'),
(229, '福集周六嫂', '', 1, 'FJZLS'),
(230, '宜宾孙正', '', 1, 'ybsz'),
(231, '叙永徐苏', '', 1, 'xyxs'),
(232, '宜宾小代', '', 1, 'YBXD'),
(233, '匡思德', '15298254454', 1, 'QSD'),
(234, '小孔', '', 1, 'xk'),
(235, '荣昌谢', '13983260779', 1, 'rcx'),
(236, '叙永李健', '', 1, 'xylj'),
(237, '小周', '', 1, 'xz'),
(238, '58', '', 1, '58'),
(239, '兴文李超', '', 1, 'xwlc'),
(240, '立石徐三', '', 1, 'lsxs'),
(241, '红星孔三姐', '', 1, 'hxksj'),
(242, '金龙邱六', '', 1, 'JLQL'),
(243, '牛耕李克军', '13281657778', 1, 'lkj'),
(244, '关四', '', 1, 'gs'),
(245, '百合龚四', '', 1, 'bhgs'),
(246, '双加熊六', '', 1, 'sjxl'),
(247, '丁三', '', 1, 'ds'),
(248, '合江王二木匠', '', 1, 'hjwej'),
(249, '李明华', '', 1, 'lmh'),
(251, 'ST', '', 1, 'ST'),
(252, '海潮许七', '', 1, 'HCXQ'),
(253, '古蔺罗七', '', 1, 'gllq'),
(254, '福集刘四', '', 1, 'FJLS'),
(255, '高坝唐加权', '', 1, 'tjq'),
(256, '叙永张司机', '', 1, 'xyzsj'),
(257, '市场张二', '', 1, 'scze'),
(258, '陈忠高', '', 1, 'czg'),
(259, '打鱼村李二', '', 1, 'dycle'),
(260, '黄弥肖五', '', 1, 'hmxw'),
(261, '华青', '', 1, 'hq'),
(262, '许幺嫂', '', 1, 'XYS'),
(263, '徐大嫂', '', 1, 'xds'),
(264, '得超', '', 1, 'dc'),
(265, '福集小林', '', 1, 'fjxl'),
(266, '杨林嫂', '', 1, 'yls'),
(267, '小夏', '', 1, 'xx'),
(268, '市场小钟', '', 1, 'scxz'),
(269, '谭高登', '', 1, 'TGD'),
(270, '合江王眼镜', '', 1, 'hjwyj'),
(271, '关口姜雪', '15928297745', 1, 'gkjx'),
(272, '荣昌谢桃子', '13983260779', 1, 'RCXTZ'),
(273, '榕山王九', '', 1, 'rswj'),
(274, '现金', '', 1, 'xj'),
(275, '红星赵江', '', 1, 'hxzj'),
(276, '川EA74D3', '', 1, '74D3'),
(277, '红星罗波妈妈', '', 1, 'HXLBMM'),
(278, '荣昌彭老五', '', 1, 'rcplw'),
(279, '兴文王三', '', 1, 'xwws'),
(280, '胡二', '', 1, 'HE'),
(281, '赤水小胡', '', 1, 'csxh'),
(282, '云龙小苏', '', 1, 'ylxs'),
(283, '张诚', '', 1, 'zc'),
(284, '宜宾孙大姐', '', 1, 'ybsdj'),
(285, '宜宾钟三', '', 1, 'ybzs'),
(286, '水清彭江', '13350177577', 1, 'sqpj'),
(287, '合江龚四', '', 1, 'hjgs'),
(288, '兴文张八', '', 1, 'xwzb'),
(289, '海鲜李六', '', 1, 'hxll'),
(290, '周杰', '', 1, 'zj'),
(291, '小冯', '', 1, 'xf'),
(292, '永川陈三', '13883658656', 1, 'yccs'),
(293, '合江刘毅', '', 1, 'hjly'),
(294, '江北张洋', '', 1, 'jbzy'),
(295, '叙永刘五眼镜', '', 1, 'xylwyj'),
(296, '古蔺明三', '15283031398', 1, 'glms'),
(297, '罗光富', '15881404861', 1, 'lgf'),
(298, '毗卢李二', '', 1, 'plle'),
(299, '合江吴', '', 1, 'hjw'),
(300, '江津老屈', '18602368979', 1, 'jjlq'),
(301, '叙永李二', '', 1, 'xyle'),
(302, '泸州汇通蓝田店', '', 1, 'lzhtlt'),
(303, '搬运老张', '', 1, 'bylz'),
(304, '福集刘眼镜', '', 1, 'fjlyj'),
(305, '泸州汇通城北店', '', 1, 'lzht'),
(306, '石二', '', 1, 'SE'),
(307, '大方彭', '', 1, 'dfp'),
(308, '蒋二妹', '', 1, 'jem'),
(309, '薛老幺', '', 1, 'XLY'),
(310, '水清江彬', '13990931084', 1, 'SQJB'),
(311, '福集陈三', '13698158308', 1, 'fjcs'),
(312, '立石何乌鱼', '', 1, 'lshwy'),
(313, '玄滩许世强', '13982747184', 1, 'xtxsq'),
(314, '欣宇分店', '', 1, 'xyfd'),
(315, '赵昭华', '13086599699', 1, 'zzh'),
(316, '李大贵', '13518492895 ', 1, 'ldg'),
(317, '白沙黄三', '', 1, 'bshs'),
(318, '陈代权', '15348206400', 1, 'cdq'),
(319, '赤水张二姐', '', 1, 'CSZEJ'),
(320, '邓川', '', 1, 'dc'),
(321, '茉莉周松', '', 1, 'mlzs'),
(323, '乌大鱼车', '', 1, 'WDYC'),
(324, '叙永曾光勇', '', 1, 'xyzgy'),
(325, '蓝田龙九', '', 1, 'LTLJ'),
(326, '小石', '', 1, 'XS'),
(327, '蓝天龙八', '', 1, 'ltlb'),
(328, '陈齐刚', '', 1, 'cqg'),
(329, '红星杨二', '', 1, 'hxye'),
(330, '张树城', '', 1, 'zsc'),
(331, '陈开贤', '', 1, 'ckx'),
(332, '摩托车吴大', '', 1, 'mtcwd'),
(333, '泸州庞五', '', 1, 'LZPW'),
(334, '宜宾李平', '', 1, 'yblp'),
(335, '蓝田欧师', '', 1, 'LTOS'),
(336, '蓝田黄四', '', 1, 'lths'),
(337, '李勇', '', 1, '18683018051'),
(338, '宝藏何志坤', '', 1, 'BZHZK'),
(339, '泸州余五', '', 1, 'LZYW'),
(340, '镇雄刘', '', 1, 'ZXL'),
(341, '纳溪白三', '', 1, 'NXBS'),
(342, '五十八公里毛大哥', '', 1, ''),
(343, '应先成', '', 1, 'yxc'),
(344, '四面山李明', '', 1, '15883020834'),
(345, '徐大', '', 1, 'xd'),
(346, '小杨刘大', '', 1, 'xyld'),
(347, '老许', '', 1, 'lx'),
(348, '赵化张二', '', 1, 'zhze'),
(349, '常二', '', 1, 'ce'),
(350, '王飞', '', 1, ''),
(351, '许基本', '', 1, 'xjq'),
(352, '许基权', '', 1, 'XJQ'),
(353, '沈海军', '', 1, 'shj'),
(354, '叙永陶成伟', '', 1, 'xytcw'),
(355, '泸州恒达维修', '', 1, 'HDWX'),
(356, '志军水产', '', 1, 'zjsc'),
(357, '肖剑', '13419166665', 1, 'xj'),
(358, '赵化张大', '', 1, 'zhzd'),
(359, '自贡李哥', '', 1, 'zglg'),
(360, '古蔺杨姐', '', 1, 'glyj'),
(361, '镇雄陈勇', '', 1, 'zxcy'),
(362, '赵汝卢', '', 1, 'ZRL18786523185'),
(363, '镇雄范林', '18869406670', 1, 'zxfl'),
(364, '古蔺多尔惠', '', 1, 'gldeh'),
(365, '云龙马儿女', '', 1, 'YLMEN'),
(366, '袁老幺', '', 1, 'yly'),
(367, '转盘何三', '18084956429', 1, 'zphs'),
(368, '毕节刘谷主', '', 1, 'bjlgz'),
(369, '赤水小王', '', 1, 'csxw'),
(370, '奇峰陈大', '', 1, 'qfcd'),
(371, '洪洋李三', '15183063680', 1, 'hyls'),
(372, '张斗民', '', 1, 'zdm'),
(373, '大洋海鲜申成贵', '13548354960', 1, 'dyhxscg'),
(374, '泸州市江阳区汇通时代商都有限公司长江蓝湾店', '', 1, 'htlw'),
(375, '贾三姐', '', 1, 'jsj'),
(376, '镇雄小杨', '', 1, 'zxxy'),
(377, '镇雄韦哥', '15287778286', 1, 'zxwg'),
(378, '梅三', '', 1, 'ms'),
(379, '况场杨二', '13982457134', 1, 'kcye'),
(380, '赵化方二', '', 1, 'zhfe'),
(381, '汇通龙南店', '', 1, 'htlnd'),
(382, '赤水柳姐', '', 1, 'cslj'),
(383, '立石张勇', '', 1, 'LSZY'),
(384, '况场赵二', '', 1, 'KCZE'),
(385, '焦滩谭六', '15983025309', 1, 'jttl'),
(386, '习水李九', '', 1, 'XSLJ'),
(387, '肖友才', '', 1, 'XYC'),
(388, '福集乌二娃', '', 1, 'fjwew'),
(389, '喻市陈三', '', 1, 'YSCS'),
(390, '南极子胡三娃', '', 1, 'njzhs'),
(391, '李建平', '', 1, 'LJP'),
(392, '福集刘老幺', '', 1, 'fjlly'),
(393, '蔡二', '', 1, 'ce'),
(394, '夏彬', '', 1, 'xb'),
(395, '长滩李大', '', 1, 'ctld'),
(396, '乌鱼周兵', '', 1, 'wyzb'),
(397, '搬运狗儿', '', 1, 'byge'),
(398, '陈清海', '', 1, 'cqh'),
(399, '富顺彭大幺叔', '', 1, 'fspdys'),
(400, '欧姐', '', 1, 'oj'),
(401, '大众酒水', '', 1, 'dzjs'),
(402, '三怪', '', 1, 'sg'),
(403, '王八', '', 1, 'wb'),
(404, '隆昌周三娃', '', 1, 'LCZSW'),
(405, '刘世贤', '', 1, 'lsx'),
(406, '邓艾华特兴', '', 1, 'TXDLH'),
(407, '纳溪王三', '', 1, 'nxws'),
(408, '徐苏驾驶员', '', 1, 'XSJSY'),
(409, '欣宇海鲜', '', 1, 'xyhx'),
(410, '市场尹哥', '', 1, 'SCYG'),
(411, '阳光海鲜', '', 1, 'yghx'),
(412, '石洞王六舅', '', 1, 'sdwlj'),
(413, 'test', NULL, 1, 'test');

CREATE TABLE `purchase` (
  `purchase_id` int(12) NOT NULL,
  `supply_id` int(10) NOT NULL,
  `sum` int(5) NOT NULL,
  `on_way` tinyint(2) NOT NULL DEFAULT '0',
  `user` varchar(32) NOT NULL,
  `time` datetime NOT NULL,
  `summary` text,
  `type` varchar(4) NOT NULL,
  `cf` int(12) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

INSERT INTO `purchase` (`purchase_id`, `supply_id`, `sum`, `on_way`, `user`, `time`, `summary`, `type`, `cf`) VALUES
(4, 1, 600, 0, 'admin', '2017-08-18 09:54:36', 'admin修改于：2017-08-18 09:56:44', 'S', NULL),
(3, 1, 12, 0, 'admin', '2017-08-18 09:53:17', '', 'S', NULL);
DELIMITER $$
CREATE TRIGGER `onBeforePurchaseDelete` BEFORE DELETE ON `purchase` FOR EACH ROW BEGIN
INSERT INTO `purchase_finish`
(`purchase_id`,`supply_id`,`sum`,`time`,`on_way`,`user`,`summary`,`type`,`cf`)
VALUES( 
OLD.purchase_id,    
    OLD.supply_id,
    OLD.sum,
    OLD.time,
    OLD.on_way,
    OLD.user,
    OLD.summary,
    OLD.type,
    OLD.cf);
END
$$
DELIMITER ;
CREATE TABLE `purchase_all` (
`purchase_id` int(12)
,`supply_id` int(11)
,`sum` int(11)
,`on_way` tinyint(4)
,`user` varchar(32)
,`time` datetime
,`summary` mediumtext
,`type` varchar(4)
,`cf` int(12)
,`finish` bigint(20)
);

CREATE TABLE `purchase_detail` (
  `purchase_detail_id` int(18) NOT NULL,
  `purchase_id` int(10) NOT NULL,
  `goods_id` int(10) NOT NULL,
  `price` decimal(5,2) NOT NULL,
  `sum` int(6) NOT NULL,
  `number` decimal(6,2) NOT NULL,
  `store_id` int(6) DEFAULT NULL,
  `inorder` varchar(15) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `purchase_detail` (`purchase_detail_id`, `purchase_id`, `goods_id`, `price`, `sum`, `number`, `store_id`, `inorder`) VALUES
(3, 3, 1, '12.00', 12, '1.00', 1, NULL),
(4, 4, 1, '12.00', 600, '50.00', 3, NULL);
DELIMITER $$
CREATE TRIGGER `purchase_detail_after_delete` AFTER DELETE ON `purchase_detail` FOR EACH ROW BEGIN
    UPDATE stock set stock.number=stock.number-old.number,stock.sum=stock.sum-old.sum 
    WHERE stock.goods_id=old.goods_id and stock.inorder=old.inorder and stock.store_id=old.store_id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `purchase_detail_after_insert` AFTER INSERT ON `purchase_detail` FOR EACH ROW BEGIN
SET @cnt=(
 select COUNT(*)  from stock where stock.goods_id=new.goods_id and stock.inorder=new.inorder and stock.store_id=new.store_id);
    if(@cnt=0) then 
    INSERT into stock (goods_id,number,sum,inorder,store_id) VALUES (new.goods_id,new.number,new.sum,new.inorder,new.store_id);
    ELSE
    UPDATE stock set stock.number=stock.number+new.number,stock.sum=stock.sum+new.sum WHERE stock.goods_id=new.goods_id and stock.inorder=new.inorder and stock.store_id=new.store_id;
    end if;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `purchase_detail_after_update` AFTER UPDATE ON `purchase_detail` FOR EACH ROW BEGIN
    UPDATE stock set stock.number=stock.number-old.number+new.number,stock.sum=stock.sum-old.sum+new.sum
    WHERE stock.goods_id=new.goods_id and stock.store_id=new.store_id and stock.inorder=new.inorder;
END
$$
DELIMITER ;

CREATE TABLE `purchase_finish` (
  `purchase_id` int(12) NOT NULL,
  `supply_id` int(10) NOT NULL,
  `sum` int(5) NOT NULL DEFAULT '0',
  `on_way` tinyint(2) NOT NULL DEFAULT '0',
  `user` varchar(32) NOT NULL,
  `time` datetime NOT NULL,
  `summary` text,
  `type` char(4) NOT NULL,
  `cf` int(12) DEFAULT NULL,
  `inorder` varchar(15) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

INSERT INTO `purchase_finish` (`purchase_id`, `supply_id`, `sum`, `on_way`, `user`, `time`, `summary`, `type`, `cf`, `inorder`) VALUES
(1, 1, 200, 0, 'admin', '2017-08-18 09:44:35', '', 'S', NULL, NULL),
(2, 1, 60, 0, 'admin', '2017-08-18 09:45:33', '', 'S', NULL, NULL);
CREATE TABLE `purchase_price` (
`supply_id` int(11)
,`goods_id` int(10)
,`price` decimal(5,2)
);

CREATE TABLE `sale` (
  `sale_id` int(12) NOT NULL,
  `member_id` int(10) NOT NULL,
  `sum` int(5) NOT NULL,
  `user` varchar(32) NOT NULL,
  `time` datetime NOT NULL,
  `summary` text,
  `type` varchar(4) NOT NULL,
  `cf` int(12) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
DELIMITER $$
CREATE TRIGGER `onBeforeSaleDelete` BEFORE DELETE ON `sale` FOR EACH ROW BEGIN
INSERT INTO `sale_finish`
(`sale_id`,`member_id`,`sum`,`time`,`user`,`summary`,`type`,`cf`)
VALUES( 
    OLD.sale_id,    
    OLD.member_id,
    OLD.sum,
    OLD.time,
    OLD.user,
    OLD.summary,
    OLD.type,
    OLD.cf);
END
$$
DELIMITER ;
CREATE TABLE `sale_all` (
`sale_id` int(12)
,`member_id` int(11)
,`sum` int(11)
,`user` varchar(32)
,`time` datetime
,`summary` mediumtext
,`type` varchar(4)
,`cf` int(12)
,`finish` bigint(20)
);

CREATE TABLE `sale_detail` (
  `sale_detail_id` int(18) NOT NULL,
  `sale_id` int(10) NOT NULL,
  `goods_id` int(10) NOT NULL,
  `price` decimal(5,2) NOT NULL,
  `sum` int(6) NOT NULL,
  `number` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
DELIMITER $$
CREATE TRIGGER `sale_Detail_after_Delete` AFTER DELETE ON `sale_detail` FOR EACH ROW BEGIN 
set @cost=(SELECT cost FROM account WHERE account.sale_detail_id=old.sale_detail_id);
DELETE FROM account WHERE account.sale_detail_id=old.sale_detail_id;
    UPDATE stock set stock.number=stock.number+old.number,stock.sum=stock.sum+@cost
    WHERE stock.goods_id=old.goods_id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `sale_Detail_after_update` AFTER UPDATE ON `sale_detail` FOR EACH ROW BEGIN
set @number=(SELECT number from stock WHERE stock.goods_id=new.goods_id);
set @stock=(SELECT sum from stock WHERE stock.goods_id=new.goods_id);
IF(@number=0) THEN
UPDATE account set cost=new.sum,income=new.sum WHERE account.sale_detail_id=new.sale_detail_id;
 UPDATE stock set stock.number=old.number-new.number,stock.sum=stock.sum-new.sum
    WHERE stock.goods_id=new.goods_id;

ELSE
UPDATE account set cost=cost+@stock*(new.number-old.number)/@number,income=new.sum WHERE account.sale_detail_id=new.sale_detail_id;

    UPDATE stock set stock.sum=stock.sum*(1-(new.number-old.number)/stock.number) ,stock.number=stock.number+old.number-new.number
    WHERE stock.goods_id=new.goods_id;
    end if;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `sale_detail_after_insert` AFTER INSERT ON `sale_detail` FOR EACH ROW BEGIN
SET @cnt=(
 select COUNT(*)  from stock where stock.goods_id=new.goods_id );
 set @number=(SELECT number from stock WHERE stock.goods_id=new.goods_id);
 set @stock=(SELECT sum from stock WHERE stock.goods_id=new.goods_id);
    if(@cnt=0||@number=0) then 
    INSERT INTO account(sale_detail_id,cost,income,subject)
    VALUES(new.sale_detail_id,new.sum,new.sum,0);
   set @number=(0-new.number);
   set @sum=(0-new.sum);
    INSERT into stock (goods_id,number,sum) VALUES (new.goods_id,@number,@sum);
    ELSE
     INSERT INTO account(sale_detail_id,cost,income,subject)
    VALUES(new.sale_detail_id,@stock*(new.number/@number),new.sum,0);
    UPDATE stock set stock.sum=stock.sum*(1-new.number/stock.number),stock.number=stock.number-new.number WHERE stock.goods_id=new.goods_id;
    end if;
   
END
$$
DELIMITER ;

CREATE TABLE `sale_finish` (
  `sale_id` int(12) NOT NULL,
  `member_id` int(10) NOT NULL,
  `sum` int(5) NOT NULL DEFAULT '0',
  `user` varchar(32) NOT NULL,
  `time` datetime NOT NULL,
  `summary` text,
  `type` char(4) NOT NULL,
  `cf` int(12) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
CREATE TABLE `sale_price` (
`goods_id` int(10)
,`member_id` int(10)
,`price` decimal(5,2)
);

CREATE TABLE `staff` (
  `staff_id` int(11) NOT NULL,
  `staff_name` varchar(45) NOT NULL,
  `staff_phone` varchar(45) DEFAULT NULL,
  `staff_sn` varchar(45) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='员工';

INSERT INTO `staff` (`staff_id`, `staff_name`, `staff_phone`, `staff_sn`) VALUES
(1, '大鱼儿', NULL, 'dye');

CREATE TABLE `stock` (
  `stock_id` int(12) NOT NULL,
  `goods_id` int(12) NOT NULL,
  `sum` decimal(12,2) NOT NULL,
  `number` decimal(10,2) NOT NULL,
  `store_id` int(6) NOT NULL,
  `inorder` varchar(15) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `stock` (`stock_id`, `goods_id`, `sum`, `number`, `store_id`, `inorder`) VALUES
(1, 1, '12.00', '1.00', 1, NULL),
(4, 1, '122.00', '3.00', 1, NULL),
(5, 1, '12.00', '1.00', 1, NULL),
(6, 1, '1200.00', '100.00', 3, NULL);

CREATE TABLE `store` (
  `store_id` int(6) NOT NULL,
  `store_name` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `store` (`store_id`, `store_name`) VALUES
(2, '商贸城18'),
(1, '商贸城19'),
(3, '海吉星');

CREATE TABLE `supply` (
  `supply_id` int(11) NOT NULL,
  `supply_name` varchar(45) NOT NULL,
  `supply_phone` varchar(45) DEFAULT NULL,
  `supply_sn` varchar(45) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='供应商';

INSERT INTO `supply` (`supply_id`, `supply_name`, `supply_phone`, `supply_sn`) VALUES
(1, '韩川', NULL, 'hc');

CREATE TABLE `unit` (
  `unit_id` int(5) NOT NULL,
  `unit_name` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `unit` (`unit_id`, `unit_name`) VALUES
(4, '个'),
(2, '件'),
(3, '包'),
(1, '斤');

CREATE TABLE `unit_price` (
  `unit_price_id` int(8) NOT NULL,
  `unit_id` int(6) NOT NULL,
  `goods_id` int(8) NOT NULL,
  `price` decimal(8,2) NOT NULL,
  `fx` decimal(8,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
DROP TABLE IF EXISTS `purchase_all`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `purchase_all`  AS  select `purchase`.`purchase_id` AS `purchase_id`,`purchase`.`supply_id` AS `supply_id`,`purchase`.`sum` AS `sum`,`purchase`.`on_way` AS `on_way`,`purchase`.`user` AS `user`,`purchase`.`time` AS `time`,`purchase`.`summary` AS `summary`,`purchase`.`type` AS `type`,`purchase`.`cf` AS `cf`,0 AS `finish` from `purchase` union select `purchase_finish`.`purchase_id` AS `purchase_id`,`purchase_finish`.`supply_id` AS `supply_id`,`purchase_finish`.`sum` AS `sum`,`purchase_finish`.`on_way` AS `on_way`,`purchase_finish`.`user` AS `user`,`purchase_finish`.`time` AS `time`,`purchase_finish`.`summary` AS `summary`,`purchase_finish`.`type` AS `type`,`purchase_finish`.`cf` AS `cf`,1 AS `finish` from `purchase_finish` ;
DROP TABLE IF EXISTS `purchase_price`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `purchase_price`  AS  select `a`.`supply_id` AS `supply_id`,`b`.`goods_id` AS `goods_id`,`b`.`price` AS `price` from (`purchase_all` `a` join `purchase_detail` `b`) where (`a`.`purchase_id` = `b`.`purchase_id`) order by `a`.`time` desc ;
DROP TABLE IF EXISTS `sale_all`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `sale_all`  AS  select `sale`.`sale_id` AS `sale_id`,`sale`.`member_id` AS `member_id`,`sale`.`sum` AS `sum`,`sale`.`user` AS `user`,`sale`.`time` AS `time`,`sale`.`summary` AS `summary`,`sale`.`type` AS `type`,`sale`.`cf` AS `cf`,0 AS `finish` from `sale` union select `sale_finish`.`sale_id` AS `sale_id`,`sale_finish`.`member_id` AS `member_id`,`sale_finish`.`sum` AS `sum`,`sale_finish`.`user` AS `user`,`sale_finish`.`time` AS `time`,`sale_finish`.`summary` AS `summary`,`sale_finish`.`type` AS `type`,`sale_finish`.`cf` AS `cf`,1 AS `finish` from `sale_finish` ;
DROP TABLE IF EXISTS `sale_price`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `sale_price`  AS  select `sale_detail`.`goods_id` AS `goods_id`,`sale`.`member_id` AS `member_id`,`sale_detail`.`price` AS `price` from (`sale` join `sale_detail`) where (`sale`.`sale_id` = `sale_detail`.`sale_id`) order by `sale`.`time` desc ;


ALTER TABLE `account`
  ADD PRIMARY KEY (`account_id`);

ALTER TABLE `bank`
  ADD PRIMARY KEY (`bank_id`),
  ADD UNIQUE KEY `bank_name` (`bank_name`);

ALTER TABLE `bdetail`
  ADD PRIMARY KEY (`bdetail`),
  ADD KEY `bank_id` (`bank_id`);

ALTER TABLE `cat`
  ADD PRIMARY KEY (`cat_id`),
  ADD UNIQUE KEY `cat_name` (`cat_name`);

ALTER TABLE `goods`
  ADD PRIMARY KEY (`goods_id`),
  ADD UNIQUE KEY `goods_name` (`goods_name`),
  ADD KEY `cat_id` (`cat_id`);

ALTER TABLE `info`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `log`
  ADD PRIMARY KEY (`log_id`);

ALTER TABLE `member`
  ADD PRIMARY KEY (`member_id`),
  ADD UNIQUE KEY `member_name_UNIQUE` (`member_name`);

ALTER TABLE `purchase`
  ADD PRIMARY KEY (`purchase_id`),
  ADD KEY `supply_id` (`supply_id`);

ALTER TABLE `purchase_detail`
  ADD PRIMARY KEY (`purchase_detail_id`),
  ADD KEY `purchase_id` (`purchase_id`),
  ADD KEY `goods_id` (`goods_id`);

ALTER TABLE `purchase_finish`
  ADD UNIQUE KEY `purchase_id` (`purchase_id`),
  ADD KEY `supply_id` (`supply_id`),
  ADD KEY `supply_id_2` (`supply_id`),
  ADD KEY `purchase_id_2` (`purchase_id`);

ALTER TABLE `sale`
  ADD PRIMARY KEY (`sale_id`);

ALTER TABLE `sale_detail`
  ADD PRIMARY KEY (`sale_detail_id`),
  ADD KEY `goods_id` (`goods_id`);

ALTER TABLE `sale_finish`
  ADD UNIQUE KEY `sale_id` (`sale_id`);

ALTER TABLE `staff`
  ADD PRIMARY KEY (`staff_id`),
  ADD UNIQUE KEY `staff_name_UNIQUE` (`staff_name`);

ALTER TABLE `stock`
  ADD PRIMARY KEY (`stock_id`),
  ADD KEY `goods_id_4` (`goods_id`);

ALTER TABLE `store`
  ADD PRIMARY KEY (`store_id`),
  ADD UNIQUE KEY `store_name` (`store_name`);

ALTER TABLE `supply`
  ADD PRIMARY KEY (`supply_id`),
  ADD UNIQUE KEY `supply_name_UNIQUE` (`supply_name`);

ALTER TABLE `unit`
  ADD PRIMARY KEY (`unit_id`),
  ADD UNIQUE KEY `unit_name` (`unit_name`);

ALTER TABLE `unit_price`
  ADD PRIMARY KEY (`unit_price_id`),
  ADD UNIQUE KEY `uk_t_1` (`unit_id`,`goods_id`);


ALTER TABLE `account`
  MODIFY `account_id` int(12) NOT NULL AUTO_INCREMENT;
ALTER TABLE `bank`
  MODIFY `bank_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;
ALTER TABLE `bdetail`
  MODIFY `bdetail` int(18) NOT NULL AUTO_INCREMENT;
ALTER TABLE `cat`
  MODIFY `cat_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
ALTER TABLE `goods`
  MODIFY `goods_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
ALTER TABLE `info`
  MODIFY `id` int(1) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
ALTER TABLE `log`
  MODIFY `log_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
ALTER TABLE `member`
  MODIFY `member_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=414;
ALTER TABLE `purchase`
  MODIFY `purchase_id` int(12) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
ALTER TABLE `purchase_detail`
  MODIFY `purchase_detail_id` int(18) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
ALTER TABLE `sale`
  MODIFY `sale_id` int(12) NOT NULL AUTO_INCREMENT;
ALTER TABLE `sale_detail`
  MODIFY `sale_detail_id` int(18) NOT NULL AUTO_INCREMENT;
ALTER TABLE `staff`
  MODIFY `staff_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
ALTER TABLE `stock`
  MODIFY `stock_id` int(12) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
ALTER TABLE `store`
  MODIFY `store_id` int(6) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
ALTER TABLE `supply`
  MODIFY `supply_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
ALTER TABLE `unit`
  MODIFY `unit_id` int(5) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
ALTER TABLE `unit_price`
  MODIFY `unit_price_id` int(8) NOT NULL AUTO_INCREMENT;

ALTER TABLE `bdetail`
  ADD CONSTRAINT `bdetail_ibfk_1` FOREIGN KEY (`bank_id`) REFERENCES `bank` (`bank_id`);

ALTER TABLE `goods`
  ADD CONSTRAINT `FK_cat_id` FOREIGN KEY (`cat_id`) REFERENCES `cat` (`cat_id`);

ALTER TABLE `purchase_detail`
  ADD CONSTRAINT `purchase_detail_ibfk_1` FOREIGN KEY (`goods_id`) REFERENCES `goods` (`goods_id`);

ALTER TABLE `sale_detail`
  ADD CONSTRAINT `sale_detail_ibfk_1` FOREIGN KEY (`goods_id`) REFERENCES `goods` (`goods_id`);

ALTER TABLE `stock`
  ADD CONSTRAINT `stock_ibfk_1` FOREIGN KEY (`goods_id`) REFERENCES `goods` (`goods_id`);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
