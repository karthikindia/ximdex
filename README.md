


# Ximdex 3.* - README

[![Build Status](https://travis-ci.org/XIMDEX/ximdex.svg?branch=develop)](https://travis-ci.org/XIMDEX/ximdex)

Ximdex CMS is a Visual Headless Content & Data Management System (CMS / DMS) with Semantic Web technologies that allows the edition and transformation of content and data to be published in different target technologies (PHP, J2EE, .NET, XML/XSLT, JS, RDF, JSON, RoR, ...) and sync to the cloud as web, datesets, ebooks or as hybrid apps for mobile devices.

* Description
  -----------

  In fact, it is a powerful and versatile decoupled Content Management Framework (CMF) to help you manage information in form of data, content or services to be published in the cloud as web portals, structured semantic repositories or linked open datasets.

  With Ximdex CMS you can mix structured and non-structured content and data, represent apps and services, aggregate information from remote sources, capture metadata, annotate it with semantic tags from the visual ontology browser, add a semantic layer and publish it using Dynamic Semantic Publishing (DSP) technologies, automatically generate suggestions to enrich your content (as images, new links or references, ...), etc.

  Ximdex is modular, based on standards (XML, XSLT, RDF, ...) and it adds a semantic layer to any managed element (doc, app, picture, metadata, video, etc.) that makes it easy to adapt information to any final exploitation format (html5, rdf, j2ee, php, json, RoR, xml/xslt, …) and publish it into the cloud as web portals, semantic portals, mobile services, linked open datasets, etc.

* Features
  --------

  - Flexible, Secure, Scalable
  - Neutral: content independence, format agnostic, free structure, open access, ...
  - Information adaptable, structurizable and semantizable applying Dynamic Semantic Publishing (DSP) techniques
  - Multichannel in the cloud
  - Visual Edition of XML + automatic transformation of XML
  - Visual Role/User/Workflow edition in an object-action UI
  - Its neutrality and flexibility allows to use any application server or language for the deployment of portals and web applications: .NET, PHP, ASP, J2EE, XML/XSLT, XHTML, HTML5, DITTA, ... Also as: EPUB, APPS ...
  - Multiple languages, multiple channels (Digital TV, Web, smartphones and tablets, ...) and multiple application servers


* Future plans
  ------------
  See our project roadmap at ROADMAP.md


* Get Involved
  ------------
  If you are interested on the power of Semantic Web for CMS this is a good starting point!.


* Availability
  ------------
  Ximdex CMS is open source with AGPL v3 (see 'LICENSE')

* Installation
  ------------
  See 'INSTALLATION.md' for the recommnended installation process.

* Ximdex Core Requirements
  ------------------------
  -  A Unix based system with PHP(>= 5.3, and some extra modules), Apache 2 webserver (with modules described in the installation guide) and MySQL(>= 5.1) database.
  -  In the client side: a modern web browser (chrome, firefox) with Javascript and cookies enabled.
  -  An internet connection if you want to use automatic recommendations (as semantic annotations) or publishing into the cloud.
 
* Ximdex developer's guide
  ------------------------

  This repo contains all the required dependencies to use Ximdex. If you want to execute tests, you have to run the next commands:
  
```{r, engine='bash'} 
  cd tests 
  docker-compose up -d ximdex 
  docker-compose run test 

```

* Important
  ------------------------

  If you are going to do a pull request ensure that you don't commit the developer dependencies. You can remove them using:
    
```{r, engine='bash'}
    composer install --no-dev
```
