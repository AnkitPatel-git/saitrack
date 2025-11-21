<!DOCTYPE HTML >
<html>
<head>
<title>PAN</title>
<meta http-equiv="content-type" content="text/html; charset=iso-8859-1"> 
<style>
html,body{margin:0;padding:0;
  -webkit-print-color-adjust:exact !important;
  print-color-adjust:exact !important;}
body{font: 76% arial,sans-serif;text-align:center}
div#container{text-align:left} 
div#container{width:650px;margin:0 auto}
div#wrapper{float:left;width:100%} 
#customers {
  font-family: Arial, Helvetica, sans-serif;
  border-collapse: collapse;
  width: 100%;
  margin-top: 150px;
}

#customers td, #customers th {
  border: 1px solid  white;
  padding: 5px;
}

#customers tr:nth-child(even){background-color:#c9e2ff;}
#customers tr:nth-child(odd){background-color: #e6effa;} 

/* #customers tr:hover {background-color: #dddddd;} */

#customers th {
  padding-top: 12px;
  padding-bottom: 5px;
  text-align: left;
  background-color: rgb(4, 34, 63);
  color: rgb(255, 255, 255);
}
  </style>
</head>
<body>
<div id="container">
<!-- <div id="header"><h1> Income Tax Department Level PAN Query </h1></div> -->
  <div id="wrapper">
    <div id="content">
        @if($data['status'] =="FAILURE")
         <table id="customers">
        <tr>
          <th colspan="3" style="text-align: center;">Income Tax Department Level PAN Query</th> 
        </tr>
        <tr>
          <td><b>Information Name</b></td>
          <td><b>Pan Holder Data</b></td> 
        </tr>
        <tr>
          <td>Applicant Full Name</td>
          <td></td> 
        </tr>
        <!--<tr>-->
        <!--  <td>PAN Alloted Date</td>-->
        <!--  <td> </td> -->
        <!--</tr>-->
        <tr>
          <td>PAN Number</td>
          <td></td> 
        </tr>
        
       <tr>
    <td>Applicant FirstName</td>
    <td></td>
</tr>
<tr>
    <td>Applicant MidName</td>
    <td></td>
</tr>
<tr>
    <td>Applicant SurName</td>
    <td></td>
</tr>

        
        <tr>
          <td>Fathers FirstName</td>
          <td></td> 
        </tr>
        <tr>
          <td>Fathers MidName</td>
          <td></td> 
        </tr>
        <tr>
          <td>Fathers SurName</td>
          <td> </td> 
        </tr>
        <tr>
          <td>Gender</td>
          <td> </td> 
        </tr>
        <tr>
          <td>DOB</td>
          <td></td> 
        </tr>
        <tr>
          <td>Applicant Category</td>
          <td></td> 
        </tr>
        <tr>
          <td>Pan Status</td>
          <td><input style="background: transparent;border: none;" value="INACTIVE"></td> 
        </tr>
        <tr>
          <td>Area Code</td>
          <td> </td> 
        </tr> 
        <tr>
          <td>Range Code</td>
          <td> </td> 
        </tr>
        <tr>
          <td>AO Code</td>
          <td> </td> 
        </tr>
        <tr>
          <td>AO Type</td>
          <td></td> 
        </tr>
        <tr>
          <td>City Name</td>
          <td></td> 
        </tr>
        <tr>
          <td>State Name</td>
          <td></td> 
        </tr>
        <tr>
          <td>Nationality</td>
          <td></td> 
        </tr>
      </table>
      @else
      <table id="customers">
        <tr>
          <th colspan="3" style="text-align: center;">Income Tax Department Level PAN Query</th> 
        </tr>
        <tr>
          <td><b>Information Name</b></td>
          <td><b>Pan Holder Data</b></td> 
        </tr>
        <tr>
          <td>Applicant Full Name</td>
          <td>{{ $data['detail']['data']['full_name'] ?? null }}<input style="background: transparent;border: none;"></td> 
        </tr>
        <!--<tr>-->
        <!--  <td>PAN Alloted Date</td>-->
        <!--  <td> </td> -->
        <!--</tr>-->
        <tr>
          <td>PAN Number</td>
          <td>{{ $data['detail']['data']['pan_number'] }}</td> 
        </tr>
        
       <tr>
    <td>Applicant FirstName</td>
    <td>{{ $data['detail']['data']['full_name_split'][0] ?? null }}</td>
</tr>
<tr>
    <td>Applicant MidName</td>
    <td>{{ $data['detail']['data']['full_name_split'][1] ?? null }}</td>
</tr>
<tr>
    <td>Applicant SurName</td>
    <td>{{ $data['detail']['data']['full_name_split'][2] ?? null }}</td>
</tr>

        
        <tr>
          <td>Fathers FirstName</td>
          <td><input style="background: transparent;border: none;"></td> 
        </tr>
        <tr>
          <td>Fathers MidName</td>
          <td><input style="background: transparent;border: none;"> </td> 
        </tr>
        <tr>
          <td>Fathers SurName</td>
          <td><input style="background: transparent;border: none;"> </td> 
        </tr>
        <tr>
          <td>Gender</td>
          <td> {{ $data['detail']['data']['gender'] }}</td> 
        </tr>
        <tr>
          <td>DOB</td>
          <td> {{ $data['detail']['data']['dob'] }}</td> 
        </tr>
        <tr>
          <td>Applicant Category</td>
          <td>{{ $data['detail']['data']['category'] }} </td> 
        </tr>
        <tr>
          <td>Pan Status</td>
          <td><input style="background: transparent;border: none;" value="ACTIVE"></td> 
        </tr>
        <tr>
          <td>Area Code</td>
          <td>{{ $data['detail']['data']['address']['zip'] }} <input style="background: transparent;border: none;"> </td> 
        </tr> 
        <tr>
          <td>Range Code</td>
          <td><input style="background: transparent;border: none;"> </td> 
        </tr>
        <tr>
          <td>AO Code</td>
          <td><input style="background: transparent;border: none;"> </td> 
        </tr>
        <tr>
          <td>AO Type</td>
          <td><input style="background: transparent;border: none;"> </td> 
        </tr>
        <tr>
          <td>City Name</td>
          <td>{{ $data['detail']['data']['address']['city'] }}<input style="background: transparent;border: none;">  </td> 
        </tr>
        <tr>
          <td>State Name</td>
          <td> {{ $data['detail']['data']['address']['state'] }}<input style="background: transparent;border: none;"> </td> 
        </tr>
        <tr>
          <td>Nationality</td>
          <td> INDIAN</td> 
        </tr>
      </table>
      @endif
    </div>
  </div>
   
  
 
</div>
</body>
</html>
