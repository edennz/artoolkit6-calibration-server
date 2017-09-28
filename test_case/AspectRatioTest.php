<?php

use PHPUnit\Framework\TestCase;



final class AspectRatioTest extends TestCase
{
    public function testDifferentResolutions()
    {
        $aspectRatios = array(
        array(1,  1,  "1:1"),    
        array(176, 144,   "11:9"),  
        array(352,  288,   "11:9"),    
        array(1280,  1024,   "5:4"),    
        array(2560,  2048,   "5:4"),
        array(320, 240,  "4:3"),
        array(320, 240,  "4:3"), 
        array(320, 240,  "4:3"), 
        array(320, 240,  "4:3"), 
        array(800, 600,  "4:3"),  
        array(832, 624,  "4:3"), 
        array(960, 720,  "4:3"), 
        array(1024,768,  "4:3"), 
        array(1152, 864,  "4:3"), 
        array(1280, 960,  "4:3"), 
        array(1400, 1050,  "4:3"), 
        array(1600, 1200,  "4:3"),
        array(2048, 1536,  "4:3"),
        array(240, 160,  "3:2"),
        array(480, 320,  "3:2"),
        array(960, 640,  "3:2"),
        array(720, 480,  "3:2"),
        array(1152, 768,  "3:2"),
        array(1280,854,  "3:2"),
        array(1440, 960,  "3:2"),   
        array(320,  200,   "8:5"),    // 1.6:   320x200, 1280x800, 1440x900, 1680x1050, 1920x1200, 2560x1600
        array(1280,  800,   "8:5"),
        array(1440,  900,   "8:5"),
        array(1680,  1050,   "8:5"),
        array(1920,  1200,   "8:5"),
        array(2560,  1600,   "8:5"),
        array(800,  480,   "5:3"),    // 1.667: 800x480, 1280x768, 1600x960
        array(1280,  768,   "5:3"),
        array(1600,  960,   "5:3"),
        array(640, 360,   "16:9"),  // 1.778: 640x360, 960x540, 1024x576, 1280x720 array(720p), 1600x900, 1920x1080 array(1080p)
        array(960, 540,   "16:9"),
        array(1024, 576,   "16:9"),
        array(1280, 720,   "16:9"),
        array(1600, 900,   "16:9"),
        array(1920, 1080,   "16:9"),
        array(864,  480,   "9:5"),    // 1.8:   864x480
        array(2040, 1080,   "17:9"),  // 1.889: 2040x1080
        array(2560, 1080,   "21:9"),  // 2.333: 2560x1080
        
        // // Some values that are close to standard ratios.
        array(1366, 768,  "16:9"), // ~1.778: 1366x768
        array(1360,  768,   "16:9"), // ~1.778: 1360x768
        array(2048, 1080,  "17:9"), // ~1.889: 2048x1080 array(2K)
        array(1024, 614,  "5:3"),   // ~1.667: 1024x614
        array(480,  368,   "4:3"),   // ~1.333: 480x368
        array(1024, 552,   "17:9"), // ~1.889: 1024x552
        array(592,  480,   "11:9"), // ~1.222: 592x480, 480x368
        array(480,  368,   "4:3"), 
        array(848,  480,   "16:9"), // ~1.767: 848x480
        array(592,  480,   "11:9"), // ~1.233: 592x480
        array(1152, 870,  "4:3")    // ~1.324: 1152x870
        );


        foreach($aspectRatios as $key => $value){
            $this->assertEquals(
                $value[2],
                calcAspectRatio($value[0],$value[1])
            );

            // $this->assertEquals(
            //     '11:9',
            //     calcAspectRatio(176,144)
            // );
        }
    }
}
?>