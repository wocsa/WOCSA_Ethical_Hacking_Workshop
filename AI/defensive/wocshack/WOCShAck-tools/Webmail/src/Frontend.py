from flask import Flask, render_template
from Pages import page

webmail = Flask(__name__)
webmail.register_blueprint(page)


@webmail.errorhandler(404)
def page404(error):
    return render_template("404.html")


@webmail.errorhandler(403)
def page403(error):
    return render_template("403.html")


webmail.run(host="0.0.0.0", port=80, debug=False)
