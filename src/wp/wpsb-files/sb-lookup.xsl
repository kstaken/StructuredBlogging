<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet 
     version="1.0" 
     xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
     xmlns:str="http://exslt.org/strings"
     xmlns:aws="http://webservices.amazon.com/AWSECommerceService/2005-10-05"
     extension-element-prefixes="str">
   
    <xsl:template match="/">        
        <items>            
            <xsl:apply-templates select="//aws:Item"/>
        </items>
    </xsl:template>
    
    <xsl:template match="aws:Item">
        <item>
            <!-- Generic fields -->
            <asin><xsl:value-of select="aws:ASIN"/></asin>
            <small-image><xsl:value-of select="aws:SmallImage/aws:URL"/></small-image>
            <medium-image><xsl:value-of select="aws:MediumImage/aws:URL"/></medium-image>
            <group><xsl:value-of select="aws:ItemAttributes/aws:ProductGroup"/></group>
            <title><xsl:value-of select="aws:ItemAttributes/aws:Title"/></title>
            <detail><xsl:value-of select="aws:DetailPageURL"/></detail>
            <upc><xsl:value-of select="aws:ItemAttributes/aws:UPC"/></upc>

            <!-- Different data types use different date fields, but we  don't care about that -->
            <date><xsl:value-of select="aws:ItemAttributes/aws:PublicationDate | aws:ItemAttributes/aws:ReleaseDate"/></date>
            
            <!-- Book specific fields -->
            <author><xsl:value-of select="aws:ItemAttributes/aws:Author"/></author>
            <publisher><xsl:value-of select="aws:ItemAttributes/aws:Publisher"/></publisher>
            <isbn><xsl:value-of select="aws:ItemAttributes/aws:ISBN"/></isbn>
            
            <!-- Music specific fields -->
            <artist><xsl:value-of select="aws:ItemAttributes/aws:Artist"/></artist>
            <label><xsl:value-of select="aws:ItemAttributes/aws:Label"/></label>
            
            <!-- Movie specific fields -->
            <rating><xsl:value-of select="aws:ItemAttributes/aws:AudienceRating"/></rating>
            <studio><xsl:value-of select="aws:ItemAttributes/aws:Studio"/></studio>            
            <length><xsl:value-of select="aws:ItemAttributes/aws:RunningTime"/><xsl:text> </xsl:text><xsl:value-of select="aws:ItemAttributes/aws:RunningTime/@Units"/></length>
            
            <!-- Software specific fields -->            
            <format><xsl:value-of select="aws:ItemAttributes/aws:Format"/></format>
            <esrb><xsl:value-of select="aws:ItemAttributes/aws:ESRBAgeRating"/></esrb>
            <manufacturer><xsl:value-of select="aws:ItemAttributes/aws:Manufacturer"/></manufacturer>            
        </item>
    </xsl:template>
</xsl:stylesheet>