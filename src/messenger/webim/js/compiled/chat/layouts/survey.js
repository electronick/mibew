/*
 Copyright 2005-2013 the original author or authors.

 Licensed under the Apache License, Version 2.0 (the "License").
 You may obtain a copy of the License at
     http://www.apache.org/licenses/LICENSE-2.0
*/
(function(a,b){a.Layouts.Survey=b.Marionette.Layout.extend({template:Handlebars.templates.survey_layout,regions:{surveyFormRegion:"#content-wrapper"},serializeData:function(){return{page:a.Objects.Models.page.toJSON()}}})})(Mibew,Backbone);
