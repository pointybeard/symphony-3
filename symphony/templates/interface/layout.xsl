<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:output method="xml"
	doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN"
	doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"
	omit-xml-declaration="yes"
	encoding="UTF-8"
	indent="yes" />
	
	<xsl:variable name="site-url" select="/data/context/system/site-url" />
	<xsl:variable name="admin-url" select="/data/context/system/admin-url" />
	
	<xsl:template match="/">
		<html>
			<head>
				<link rel="stylesheet" href="{$admin-url}/assets/styles/admin.css" media="screen" type="text/css" />
				
				<!-- JavaScript -->
				<script src="{$admin-url}/assets/scripts/jquery.js" type="text/javascript"></script>
				<script src="{$admin-url}/assets/scripts/drawer.js" type="text/javascript"></script>
			</head>
			<body>
				<div id="control">
					<p id="sitename"><a href="">My Website</a></p>
					<p id="powered">Symphony 3.0 alpha</p>
					
					<xsl:apply-templates select="data/navigation"/>
				</div>
				<div id="drawer">
					<h2>About this Shit</h2>
					<p>All kinds of awesome stuff will go here</p>
				</div>
				<div id="view">
					<h1><xsl:value-of select="data/context/view/title"/></h1>
					<xsl:apply-templates select="data/actions"/>
					<xsl:apply-templates select="." mode="view"/>
				</div>
			</body>
		</html>
	</xsl:template>
	
	<xsl:template match="actions">
		<ul id="actions">
			<xsl:apply-templates select="action"/>
		</ul>
	</xsl:template>
	
	<xsl:template match="actions/action">
		<li>
			<a href="{callback}" class="{type}"><xsl:value-of select="name"/></a>
		</li>
	</xsl:template>
	
<!--
	Navigation
-->
	<xsl:template match="navigation">
		<ul id="nav">
			<xsl:apply-templates select="group"/>
		</ul>
	</xsl:template>
	
	<xsl:template match="navigation/group">
		<li id="{@handle}" data-group-handle="{@handle}">
			<!-- Temporarily wrap in SPAN just so it'll nest neatly :P -->
			<span>
				<xsl:value-of select="@name"/>
			</span>
			<a href="#" class="toggle">
				<xsl:text>&#9662;</xsl:text>
			</a>
			
			<xsl:if test="item[@visible = 'yes']">
				<ul>
					<xsl:apply-templates select="item" />
				</ul>
			</xsl:if>
		</li>
	</xsl:template>
	
	<xsl:template match="navigation/group//item" />
	
	<xsl:template match="navigation/group/item[@visible = 'yes']">
		<li id="{name/@handle}">
			<a href="{$admin-url}/{@link}">
				<xsl:if test="@active = 'yes' or .//item/@active = 'yes'">
					<xsl:attribute name="class">current</xsl:attribute>
				</xsl:if>
				
				<xsl:value-of select="@name"/>
			</a>
			
			<xsl:apply-templates select="item" />
		</li>
	</xsl:template>
	
	<xsl:template match="navigation/group/item/item[@visible = 'yes']">
		<a href="{$admin-url}/{@link}" class="quick create">
			<xsl:value-of select="@name"/>
		</a>
	</xsl:template>
</xsl:stylesheet>
