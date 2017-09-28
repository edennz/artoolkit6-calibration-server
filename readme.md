# ARToolKit Camera Calibration Server

This is a set of webapps that allow the user to run their own server of ARToolKit camera calibration parameters. This, along with apps to generate calibrations, and the runtime client component, constitute a system for distributed camera calibration generation and retrieval.

## Why is this useful?
Accurate knowledge of the intrinsic optical properties of the camera in an AR system is critical to robust tracking. ARToolKit includes an offline calibration process to calculate the intrinsic properties. The calibration tool produces a calibration file, which can be substituted into the ARToolKit runtime file system to improve tracking. However, this tool is not intended for use by untrained users, and if performed by the app developer, provides no way for the developer to provide add support for devices after the app is published.

ARToolKit already provides a function whereby it queriess a server for calibration data for the device on which it is running, but previously, users had to depend on the server component run by the ARToolKit project administrators. With these webapps, an app developer can run their own calibration server, and using the calibration apps (available separately) upload their own calibration data to it. They can then configure the ARToolKit runtime to retrieve calibrations from that server.

This allows app developers to have a greater degree of control over the end-user experience.

## Setup
The webapps are designed to run on a simple PHP+MySQL stack. Once copied onto your web server, you need to edit the file `server_data.php`, adding the details for your database server and user, and choosing a token which clients wanting to upload calibrations to your server will need to know. For security reasons, no upload token has been presupplied. The download token is set to the default token in the ARToolKit source, but you might wish to edit this if you want to prevent other users from using your calibration server.

## FAQs
**Q: Why this distributed approach? Why not automatic calibration?**
A: Automatic calibration is desirable, but poses practical limitations in that it usually requires untrained users to perform some specific procedure such as viewing a calibration pattern. We believe that having expert users carry out the calibration and then sharing the results makes for a better end-user experience.

**Q: Does this generate a lot of network usage on the users device?**
A: No. Calibration data for a specific device is less than 1 Kb of data. Additionally, the calibration information is cached on-device for up to 1 year.

**Q: If I don't want to run this server, can I provide calibration data to users of my app any other way?**
A: Yes. The automated system includes capacity for pre-loading of calibration data into the local cache. Of course, you can also bypass or modify the operation of the implementation in the client app.

**Q: Does this introduce any privacy or security considerations?**
A: No. By default, retrieval of calibration data by ARToolKit is done over an encrypted HTTPS connection. No user-identifiable information is transmitted. The data transmitted consists of the type and OS version of the user's device. This is necessary to enable the search.

Philip Lamb

2017-09-28


