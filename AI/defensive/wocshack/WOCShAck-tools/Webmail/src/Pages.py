from flask import Blueprint, render_template, request, redirect, url_for, make_response, abort
from Backend import Handler
import new_user_email_content
import hashlib

page = Blueprint(__name__, "pages")
handler = Handler()


def is_connected():
    return request.cookies.get('Id') == handler.get_cookie(request.cookies.get('Username'))


@page.route("/")
def index():
    username = request.cookies.get('Username')
    if username is not None and request.cookies.get('Id') == handler.get_cookie(username):
        return redirect(url_for("Pages.client"))
    return render_template("index.html")


@page.route("/privacy-policy")
def privacy_policy():
    return render_template("privacy_policy.html")


@page.route("/send")
def send():
    source = request.args.get('source')
    destination = request.args.get('destination')
    subject = request.args.get('subject')
    content = request.args.get('content')
    answer = handler.send_email(source, destination, subject, content)
    return f"{answer}"


@page.route("/client/mail")
def mail():
    if not is_connected():
        return redirect(url_for("Pages.connect"))
    username = username = request.cookies.get('Username')
    email_id = request.args.get('email')
    email = handler.get_email_by_id(email_id)
    try:
        if username == email[0][2]:
            return render_template("email.html", email=email[0])
    except:
        abort(403)


@page.route("/client/settings/delete_account", methods=["POST"])
def delete_account():
    if not is_connected():
        return redirect(url_for("Pages.connect"))
    username = request.cookies.get("Username")
    emails = handler.get_emails(username)
    password = request.form.get("password")
    req = handler.delete_user(username, password)
    if req == 0:
        return redirect(url_for("Pages.disconnect"))
    elif req == -2:
        return render_template("wrong_password_update.html", username=username, emails=emails)
    abort(403)


@page.route("/client/settings/change_password", methods=["POST"])
def change_password():
    if not is_connected():
        return redirect(url_for("Pages.connect"))
    username = request.cookies.get("Username")
    emails = handler.get_emails(username)
    password = request.form.get("password")
    new = request.form.get("new_password")
    conf_new = request.form.get("confirm_new_password")
    if new == conf_new:
        req = handler.update_user_password(username, password, new)
        if req == 0:
            username = username.replace("@tbox.traced", "")
            response = make_response(render_template("password_updated_successfully.html", username=username, emails=emails))
            response.set_cookie("Id", f"{handler.get_cookie(username)}", max_age=20 * 60, httponly=True, secure=False)
            return response
        elif req == -2:
            username = username.replace("@tbox.traced", "")
            return render_template("wrong_password_update.html", username=username, emails=emails)

    username = username.replace("@tbox.traced", "")
    return render_template("passwords_do_not_match_update.html", username=username, emails=emails)


@page.route("/settings")
def settings():
    if not is_connected():
        return redirect(url_for("Pages.connect"))
    username = request.cookies.get('Username')
    emails = handler.get_emails(username)
    username = username.replace("@tbox.traced", "")
    return render_template("settings.html", username=username, emails=emails)


@page.route("/client")
def client():
    print(handler.get_cookie(request.cookies.get('Username')))
    if not is_connected():
        return redirect(url_for("Pages.connect"))
    username = request.cookies.get('Username')
    emails = handler.get_emails(username)[::-1]
    print(emails)
    username = username.replace("@tbox.traced", "")
    return render_template("client.html", username=username, emails=emails)


# To handle Sign Up
@page.route('/signup', methods=['GET', 'POST'])
def signup():
    if request.method == 'POST':
        # Grab the value from the input fields
        username = request.form.get('Ufield')
        password = request.form.get('Pfield')
        confirm_password = request.form.get('CPfield')
        username += "@tbox.traced"
        username = username.casefold()
        # Perform backend logic for user verification (adjust this part as needed)
        req = handler.verify_user_availability(username, password, confirm_password)
        if req == -1:
            return render_template("username_already_exists.html")
        if req == -2:
            return render_template("password_length_error.html")

        if req == -3:
            return render_template("passwords_do_not_match.html")

        if req == 0:
            hashed_password = hashlib.sha256(password.encode()).hexdigest()
            handler.add_user(username, hashed_password)
            response = make_response(redirect(url_for("Pages.client", username=username)))
            response.set_cookie("Id", f"{handler.get_cookie(username)}", max_age=20 * 60, httponly=True, secure=False)
            response.set_cookie("Username", f"{username}", max_age=20 * 60, httponly=True, secure=False)
            handler.send_email("TBox", username, "Welcome !", new_user_email_content.content)
            return response
    # If GET request, render the signup form
    username = request.cookies.get('Username')
    if username is not None and request.cookies.get('Id') == handler.get_cookie(username):
        return redirect(url_for("Pages.client"))
    return render_template("signup.html")


@page.route('/connect', methods=['GET', 'POST'])
def connect():
    username = request.cookies.get('Username')
    if username is not None and request.cookies.get('Id') == handler.get_cookie(username):
        return redirect(url_for("Pages.client"))

    if request.method == 'POST':
        # Grab the value from the input fields
        username = request.form.get('Ufield')
        password = request.form.get('Pfield')
        # Perform backend logic for user verification (adjust this part as needed)
        username.casefold()
        req = handler.check_connect(username, password)
        if req == -1:
            return render_template("unknown_user.html")
        if req == -2:
            return render_template("wrong_password.html")
        if req == 0:
            response = make_response(redirect(url_for("Pages.client")))
            response.set_cookie("Id", f"{handler.get_cookie(username)}", max_age=20 * 60, httponly=True, secure=False)
            response.set_cookie("Username", f"{username}", max_age=20 * 60, httponly=True, secure=False)
            return response
        # If GET request, render the signup form
    return render_template("connect.html")


@page.route("/client/disconnect")
def disconnect():
    response = make_response(redirect(url_for("Pages.index")))
    response.delete_cookie("Username")
    response.delete_cookie("Id")
    return response
