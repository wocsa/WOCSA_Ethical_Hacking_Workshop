from flask import Flask, render_template, request, redirect, url_for, session, flash, jsonify
from flask_sqlalchemy import SQLAlchemy
from sqlalchemy.dialects.postgresql import UUID 
from werkzeug.security import generate_password_hash, check_password_hash
import hashlib
import uuid
import os

app = Flask(__name__)
app.config['SQLALCHEMY_DATABASE_URI'] = 'sqlite:///database.db'
app.config['SQLALCHEMY_TRACK_MODIFICATIONS'] = False
app.secret_key = os.urandom(24)  # For session security

db = SQLAlchemy(app)

class User(db.Model):
    id = db.Column(db.String(36), primary_key=True, default=lambda: str(uuid.uuid4()))
    username = db.Column(db.String(80), unique=True, nullable=False)
    password_hash = db.Column(db.String(255), nullable=False)
    
    def set_password(self, password):
        self.password_hash = generate_password_hash(password, method='pbkdf2:sha256')
        
    def check_password(self, password):
        return check_password_hash(self.password_hash, password)

class Jail(db.Model):
    id = db.Column(db.String(36), primary_key=True, default=lambda: str(uuid.uuid4()))
    name = db.Column(db.String(80), unique=True, nullable=False)

with app.app_context():
    db.create_all()
    
@app.route('/delete_user/<string:user_id>', methods=['POST'])
def delete_user(user_id):
    if 'user_id' not in session:
        flash("Unauthorized", "danger")
        return redirect(url_for('login'))

    user = User.query.get(user_id)
    if not user:
        flash("User not found.", "danger")
        return redirect(url_for('admin'))

    if user.id == session.get('user_id'):
        flash("You cannot delete yourself while logged in.", "warning")
        return redirect(url_for('admin'))

    db.session.delete(user)
    db.session.commit()
    flash(f'User \"{user.username}\" deleted.', "success")
    return redirect(url_for('admin'))



@app.route('/delete_jail/<string:jail_id>', methods=['POST'])
def delete_jail(jail_id):
    if 'user_id' not in session:
        flash("Unauthorized", "danger")
        return redirect(url_for('login'))

    jail = Jail.query.get(jail_id)
    if jail:
        db.session.delete(jail)
        db.session.commit()
        flash(f'Jail "{jail.name}" deleted.', "success")
    else:
        flash("Jail not found.", "danger")

    return redirect(url_for('admin'))


@app.route('/', methods=['GET', 'POST'])
def index():
    jails = Jail.query.all()
    total_jails = len(jails)

    # Get client IP
    client_ip = request.remote_addr

    if 'validated_jails' not in session:
        session['validated_jails'] = []

    if request.method == 'POST':
        jail_id = request.form.get('jail_id')
        input_value = request.form.get('input_value')

        # Generate expected hash
        combined = jail_id + client_ip
        expected_hash = hashlib.sha256(combined.encode()).hexdigest()[:10]

        if input_value == expected_hash:
            if jail_id not in session['validated_jails']:
                session['validated_jails'].append(jail_id)
                session.modified = True
                flash('Correct! Progress updated.', 'success')
            else:
                flash('Already validated this jail.', 'info')
        else:
            flash('Incorrect code.', 'danger')

        return redirect(url_for('index'))

    validated_count = len(session.get('validated_jails', []))

    return render_template("index.html", jails=jails,
                           validated_count=validated_count,
                           total_jails=total_jails)


@app.route('/login', methods=['GET', 'POST'])
def login():
    if request.method == 'POST':
        username = request.form['username']
        password = request.form['password']
        user = User.query.filter_by(username=username).first()
        
        if user and user.check_password(password):
            session['user_id'] = user.id
            session['username'] = user.username
            flash("Logged in successfully.", "success")
            return redirect(url_for('admin'))
        else:
            flash("Invalid username or password", "danger")
    
    return render_template("login.html")


@app.route('/logout')
def logout():
    session.clear()
    flash("Logged out successfully.", "info")
    return redirect(url_for('index'))
    

@app.route('/admin', methods=['GET', 'POST'])
def admin():
    if 'user_id' not in session:
        flash("Please log in to access the admin page", "warning")
        return redirect(url_for('login'))

    if request.method == 'POST':
        form_type = request.form.get('form_type')

        if form_type == 'add_user':
            username = request.form.get('username')
            password = request.form.get('password')
            if User.query.filter_by(username=username).first():
                flash('User already exists!', 'danger')
            else:
                new_user = User(username=username)
                new_user.set_password(password)
                db.session.add(new_user)
                db.session.commit()
                flash(f'User "{username}" added successfully!', 'success')

        elif form_type == 'add_jail':
            jail_name = request.form.get('jail_name')
            if Jail.query.filter_by(name=jail_name).first():
                flash('Jail already exists!', 'danger')
            else:
                new_jail = Jail(name=jail_name)
                db.session.add(new_jail)
                db.session.commit()
                flash(f'Jail "{jail_name}" added successfully!', 'success')

        return redirect(url_for('admin'))  # Prevent resubmission on refresh

    # Show users and jails in admin page
    users = User.query.all()
    jails = Jail.query.all()
    return render_template("admin.html", username=session.get('username'), users=users, jails=jails)
    
@app.route('/get_jail_uuid/<string:jail_name>', methods=['GET'])
def get_jail_uuid(jail_name):
    jail = Jail.query.filter_by(name=jail_name).first()
    if jail:
        return jsonify({'jail_name': jail.name, 'uuid': jail.id})
    else:
        return jsonify({'error': 'Jail not found'}), 404
        
@app.route('/get_jails', methods=['GET'])
def get_all_jails():
    jails = Jail.query.all()
    jail_list = [{'name': jail.name, 'uuid': jail.id} for jail in jails]
    return jsonify(jail_list)


if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000, debug=False)

