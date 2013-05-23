<?
include('header.php');
?>

<div id="loadingIndicator" class="loadingIndicator">
  <div style="background:url(./assets/img/ajax-loader.gif) no-repeat center center; height:100px;"></div>
</div>

<script src="./assets/OpenLayers/OpenLayers.light.js"></script>
<script src="./assets/js/geojsontools.js"></script>
<style>
    img.olTileImage {
        max-width: none;
      }


    .olControlAttribution{
    bottom:0px;
    }


      .mapContainer{
          height:300px; 
          width:300px;
      }
      @media (min-width: 500px) {
      .mapContainer{
          height:500px; 
          width:500px;
      }
    

</style>

<div class="container">
  <div id="routeInformation" class="span5">

          
  </div>

    <div class="span7 mapContainer">
      <div id="map" style="height: 100%; width:100%;">
      </div>
    </div>  
  </div>

  
<script type="text/javascript">

  (function(){
    var s = window.location.search.substring(1).split('&');
      if(!s.length) return;
        var c = {};
        for(var i  = 0; i < s.length; i++)  {
          var parts = s[i].split('=');
          c[unescape(parts[0])] = unescape(parts[1]);
        }
      window.$_GET = function(name){return name ? c[name] : c;}
  }())

  function convertToLocalTime(serverDate) {
      var dt = new Date(Date.parse(serverDate));
      var localDate = dt;


      var gmt = localDate;
          var min = gmt.getTime() / 1000 / 60; // convert gmt date to minutes
          var localNow = new Date().getTimezoneOffset(); // get the timezone
          // offset in minutes
          var localTime = min - localNow; // get the local time

      var dateStr = new Date(localTime * 1000 * 60);
      var d = dateStr.getDate();
      var m = dateStr.getMonth() + 1;
      var y = dateStr.getFullYear();

      var totalSec = dateStr.getTime() / 1000;
      var hours = parseInt( totalSec / 3600 ) % 24;
      var minutes = parseInt( totalSec / 60 ) % 60;


      return '' + y + '-' + (m<=9 ? '0' + m : m) + '-' + (d <= 9 ? '0' + d : d) + ' ' + hours +':'+ minutes;
    }

  function addRouteInformation(name, start, end){
      $('#routeInformation').append('<h2>'+name+'</h2><p>Start: '+start+'</p><p>End: '+end+'</p><p><a class="btn" href="graph.php?id='+$_GET(['id'])+'">Graphs</a><a class="btn" href="heatmap.php?id='+$_GET(['id'])+'">Thematic maps</a></p>');
  }

  function onFeatureSelect(feature){
    console.log(feature);
  }

  function onFeatureUnselect(feature){
      
  }


    var map = new OpenLayers.Map('map');
    var mapnik = new OpenLayers.Layer.OSM();
    map.addLayer(mapnik);
    map.setCenter(new OpenLayers.LonLat(7.9,51,9) // Center of the map
      .transform(
        new OpenLayers.Projection("EPSG:4326"), // transform from WGS 1984
        new OpenLayers.Projection("EPSG:900913") // to Spherical Mercator Projection
      ),8
    );
    
  
  //var styleMap = new OpenLayers.StyleMap({pointRadius: 10});
  var co2_style = new OpenLayers.StyleMap(
    { 
            "default": new OpenLayers.Style({ 
                fillColor: "${getColor}",
                strokeWidth: 1,             
                strokeColor: "#000", 
                fillOpacity: 1,
                pointRadius: 10//"${getSize}"
                //label: "${getLabel}"                  
            },
            {
                context: {
                    getColor : function (feature) {
                        return feature.attributes.phenomenons.testphenomenon1.value > 20 ? '#FF0000' :
                               feature.attributes.phenomenons.testphenomenon1.value > 10 ? '#FF5A08' :
                                                                  '#08FF41' ;
                    },
          getSize: function(feature) {
            console.log(100 / feature.layer.map.getResolution());
            return 100 / feature.layer.map.getResolution();
          }
                } 
            })
    }
  );
  var geojson_layer = new OpenLayers.Layer.Vector("Measurements",{styleMap: co2_style});
  var geojson_line = new OpenLayers.Layer.Vector("lines");
                  
    
    var geojson_format = new OpenLayers.Format.GeoJSON({
                'internalProjection': new OpenLayers.Projection("EPSG:900913"),
                'externalProjection': new OpenLayers.Projection("EPSG:4326")
            });
 

  map.addLayer(geojson_line);
  map.addLayer(geojson_layer);



    selectControl = new OpenLayers.Control.SelectFeature(geojson_layer, {
            onSelect: onFeatureSelect,
            onUnselect: onFeatureUnselect
        });
    map.addControl(selectControl);
    selectControl.activate();
      


  //GET the information about the specific track
  $.get('assets/includes/users.php?track='+$_GET(['id']), function(data) {
    if(data == 400 || data == 401 || data == 402 || data == 403 || data == 404){
        console.log('error in getting tracks');
        $('#loadingIndicator').hide();
    }else{
      geojson_layer.addFeatures(geojson_format.read(data));
      map.zoomToExtent(geojson_layer.getDataExtent());

      console.log(JSON.stringify(GeoJSONTools.points_to_lineString(data)));
      geojson_line.addFeatures(geojson_format.read(JSON.stringify(GeoJSONTools.points_to_lineString(data).features)));


      data = JSON.parse(data);
      addRouteInformation(data.properties.name, convertToLocalTime(data.features[0].properties.time), convertToLocalTime(data.features[data.features.length - 1].properties.time));

      $('#loadingIndicator').hide();
    }
    
  });


</script>   

<?
include('footer.php');
?>