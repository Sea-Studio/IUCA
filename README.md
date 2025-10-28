# 瀚海云创IDC信息查询平台 V1.1

基于IUCA IDC登记系统的开源IDC信息查询与管理平台。

## 平台简介

瀚海云创IDC信息查询平台致力于创造一个更好的IDC运营环境，让用户选择IDC时拥有一个可靠的查询渠道，防止用户踩坑，促进IDC行业的健康发展。

注：本平台已加入IUCA。

## 演示站点

**瀚海云创IDC查询演示站：**
- **演示地址**：https://demo.idc.amadb.cn/
- **后台管理**：https://demo.idc.amadb.cn/admin
- **演示账号**：admin
- **演示密码**：123456
- **自动重置**：每6小时自动重置数据

**原项目信息：**
- 基于 IUCA IDC登记系统 V1.1
- IUCA官网：https://iuca.l1.gs/
- IUCA全称：IDC United Certification Association (IDC联合认证协会)

## 功能特性

- **IDC信息查询** - 支持按名称、域名、标识码查询
- **黑名单查询** - 验证IDC联系方式是否在黑名单中
- **IDC信息登记** - 规范的IDC信息注册流程
- **认证体系** - 多级认证标识（未认证/普通认证/高级认证/企业认证）
- **状态监控** - 实时显示IDC运营状态（正常/跑路/倒闭/异常）
- **安全验证** - 邮箱验证码机制保障数据安全
- **响应式设计** - 完美支持电脑和移动设备访问
- **自动维护** - 演示站数据定期自动重置

## 安装部署

### 环境要求
- PHP 7.2+
- MySQL 5.6+
- Web服务器 (Apache/Nginx)

### 安装步骤

1. **配置数据库**
   - 在 `include/mysql_config.php` 文件中填写数据库连接信息
   - 确保数据库用户有创建表的权限

2. **运行安装程序**
   - 访问根目录下的 `install.php`
   - 按照提示完成系统安装
   - **安装完成后建议删除 install.php 文件**

3. **邮箱配置** (可选)
   - 配置 `include/email_config.php` 以启用邮件发送功能
   - 用于发送验证码等通知邮件

## 文件结构
瀚海云创IDC查询平台/
├── 前端页面/
│ ├── index.php # 系统首页
│ ├── blacklist.php # 黑名单查询页面
│ ├── register.php # IDC注册页面
│ ├── edit.php # 信息修改页面
│ └── install.php # 系统安装页面
├── 后台管理/
│ ├── login.php # 后台登录
│ ├── index.php # 后台首页
│ ├── idc_manage.php # IDC信息管理
│ ├── blacklist_manage.php # 黑名单管理
│ ├── user_manage.php # 用户管理
│ └── system_settings.php # 系统设置
├── 核心功能/
│ ├── db.php # 数据库连接
│ ├── functions.php # 通用工具函数
│ ├── blacklist_functions.php # 黑名单功能函数
│ ├── init_db.php # 数据库初始化
│ ├── header.php # 通用页头模板
│ ├── footer.php # 通用页脚模板
│ ├── EmailService.php # 邮件服务类
│ ├── MailService.php # 邮件发送类
│ ├── send_captcha.php # 验证码发送接口
│ ├── demo_mode.php # 演示模式配置
│ ├── demo_db_reset.php # 数据库重置功能
│ ├── mysql_config.php # 数据库配置文件
│ ├── email_config.php # 邮箱配置文件
│ └── dome.sql # 数据库结构文件
├── 静态资源/
│ ├── css/
│ │ └── style.css # 主样式表
│ ├── js/
│ │ └── main.js # 主JavaScript文件
│ └── images/
│ ├── gray_v.svg # 未认证图标
│ ├── orange_v.svg # 普通认证图标
│ ├── red_v.svg # 高级认证图标
│ ├── blue_v.svg # 企业认证图标
│ └── [其他图片资源]
├── 演示站专用/
│ ├── demo_reset.php # 演示站重置脚本
│ ├── scripts/
│ │ ├── demo_reset.sh # 自动重置脚本
│ │ └── monitor_demo.sh # 监控脚本
│ └── logs/
│ └── [运行日志文件]
└── 配置文件示例/
├── mysql_config.php.example # 数据库配置示例
└── email_config.php.example # 邮箱配置示例

## 核心功能模块

### 数据库模块
- `db.php` - 数据库连接管理
- `init_db.php` - 数据库表结构初始化
- `dome.sql` - 完整的SQL表结构

### 业务逻辑模块
- `functions.php` - 通用功能函数
- `blacklist_functions.php` - 黑名单相关功能
- `EmailService.php` - 邮件服务封装

### 模板模块
- `header.php` - 统一页面头部
- `footer.php` - 统一页面底部

### 配置模块
- `mysql_config.php` - 数据库配置
- `email_config.php` - 邮件服务配置
- `demo_mode.php` - 演示模式开关

## 安装配置

### 数据库配置
编辑 `include/mysql_config.php`：
邮箱配置（可选）
编辑 include/email_config.php 配置SMTP信息以启用邮件功能。

演示站特色
自动重置机制
每6小时自动重置所有数据
保持演示环境始终干净
管理员账号自动恢复

安全防护
防SQL注入攻击
文件上传安全检测

许可证
本项目基于 MPL (Mozilla Public License) 2.0 开源协议发布。

主要义务：
保留原始版权声明和许可证信息
对源码的修改必须以相同许可证发布
可以与其他许可证的代码结合使用

使用限制：
禁止用于任何商业活动
必须遵守MPL协议的所有条款

注意事项
安全建议：生产环境请删除安装文件并加强服务器安全配置
数据备份：定期备份数据库，防止数据丢失
协议遵守：严格遵守MPL开源协议，不得用于商业用途
演示站说明：演示站数据会定期重置，请勿上传重要信息

技术支持
如遇到技术问题，请参考：
检查PHP错误日志
验证数据库连接配置
确认文件权限设置
访问演示站查看功能效果

------------------------------------------

更新日志
V1.1 (2025-10)
基于IUCA系统构建

品牌更名为瀚海云创

完善演示站功能

优化移动端体验

加强安全防护机制

免责声明
本平台仅提供信息查询服务，不对IDC服务商的实际情况承担保证责任。用户应自行核实IDC服务商的资质和信誉。

------------------------------------------

最后更新：2025年10月
