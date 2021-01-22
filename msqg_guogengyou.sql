-- phpMyAdmin SQL Dump
-- version 4.4.15.10
-- https://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: 2021-01-22 22:20:28
-- 服务器版本： 5.6.49-log
-- PHP Version: 5.6.40

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `msqg_guogengyou`
--

-- --------------------------------------------------------

--
-- 表的结构 `admin`
--

CREATE TABLE IF NOT EXISTS `admin` (
  `name` char(10) NOT NULL,
  `password` char(50) NOT NULL COMMENT 'md5加盐',
  `email` char(50) NOT NULL COMMENT '不可通过前端修改',
  `email_code` char(10) NOT NULL COMMENT '邮箱验证码',
  `id` int(10) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

--
-- 转存表中的数据 `admin`
--

INSERT INTO `admin` (`name`, `password`, `email`, `email_code`, `id`) VALUES
('admin', '1133', '2001210254@stu.pku.edu.cn', '3393', 1);

-- --------------------------------------------------------

--
-- 表的结构 `admin_log`
--

CREATE TABLE IF NOT EXISTS `admin_log` (
  `querystring` char(50) NOT NULL,
  `c` char(20) NOT NULL,
  `a` char(20) NOT NULL,
  `ip` char(50) NOT NULL,
  `time` char(50) NOT NULL,
  `id` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- 转存表中的数据 `admin_log`
--

INSERT INTO `admin_log` (`querystring`, `c`, `a`, `ip`, `time`, `id`) VALUES
('', 'Index', 'change_userinfo', '2093829611', '1611322330', 0);

-- --------------------------------------------------------

--
-- 表的结构 `goods`
--

CREATE TABLE IF NOT EXISTS `goods` (
  `type` char(10) NOT NULL COMMENT '什么商品',
  `title` char(50) NOT NULL COMMENT '商品标题',
  `detail` char(100) NOT NULL COMMENT '商品详情',
  `weight` int(10) NOT NULL COMMENT '几斤',
  `discount_number` int(10) NOT NULL COMMENT '前多少名有优惠',
  `discount_price` float NOT NULL COMMENT '优惠价',
  `normal_price` float NOT NULL COMMENT '普通价格',
  `stock_number` int(10) NOT NULL COMMENT '库存',
  `id` int(100) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

--
-- 转存表中的数据 `goods`
--

INSERT INTO `goods` (`type`, `title`, `detail`, `weight`, `discount_number`, `discount_price`, `normal_price`, `stock_number`, `id`) VALUES
('cherry', '智利进口车厘子3斤装新鲜水果当季樱桃特大整箱顺丰包邮每人限购1次', '5月1日14点开抢！前100名享特优价 79元，其余享优惠价 100元。', 3, 100, 79, 100, 1000, 1);

-- --------------------------------------------------------

--
-- 表的结构 `ms_orders`
--

CREATE TABLE IF NOT EXISTS `ms_orders` (
  `email` char(50) NOT NULL,
  `address` char(50) NOT NULL COMMENT '快递地址（支付时选择）',
  `name` char(10) NOT NULL COMMENT '收件人姓名',
  `phonenum` char(20) NOT NULL COMMENT '收获手机号（支付时填写）',
  `order_status` char(10) NOT NULL COMMENT '订单状态：未抢到0、抢到特惠价1、抢到优惠价2、支付成功3',
  `time_start` char(20) NOT NULL COMMENT '订单创建时间',
  `time_end` char(20) NOT NULL COMMENT '订单结束时间(收获、退单)',
  `id` int(100) NOT NULL COMMENT '订单号'
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

--
-- 转存表中的数据 `ms_orders`
--

INSERT INTO `ms_orders` (`email`, `address`, `name`, `phonenum`, `order_status`, `time_start`, `time_end`, `id`) VALUES
('1118513116992@163.com', '', 'abc', '18811112222', '1', 'xxx', 'xxx', 1),
('"18513116992@163.com"', '', '', '', '"1"', '', '', 2);

-- --------------------------------------------------------

--
-- 表的结构 `user`
--

CREATE TABLE IF NOT EXISTS `user` (
  `email` char(50) NOT NULL COMMENT '邮箱，登录名',
  `password` char(50) NOT NULL COMMENT 'md5加盐',
  `name` char(10) NOT NULL COMMENT '用户昵称',
  `user_status` int(10) NOT NULL COMMENT '0为不可用',
  `email_code` char(10) NOT NULL COMMENT '当前邮箱验证码',
  `id` int(100) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;

--
-- 转存表中的数据 `user`
--

INSERT INTO `user` (`email`, `password`, `name`, `user_status`, `email_code`, `id`) VALUES
('18513116992@163.com', '4444', '2001210254', 1, 'd709', 1),
('2001210434@stu.pku.edu.cn', '1234', '大王宏博', 1, '5jkt', 2),
('2001210445@stu.pku.edu.cn', '6d95a6aecc6803a74d0675e178bae846', '王旭升', 1, '6ypt', 3),
('984186311@qq.com', '9999', '耕佑郭', 1, '', 6);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `goods`
--
ALTER TABLE `goods`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ms_orders`
--
ALTER TABLE `ms_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id` (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `goods`
--
ALTER TABLE `goods`
  MODIFY `id` int(100) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `ms_orders`
--
ALTER TABLE `ms_orders`
  MODIFY `id` int(100) NOT NULL AUTO_INCREMENT COMMENT '订单号',AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(100) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=7;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
