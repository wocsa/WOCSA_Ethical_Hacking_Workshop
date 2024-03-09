public class Challenge1 {
    private static final String seed = "kH9mPjH7d5d3";
    public static Integer generatePassword(String name) {
        int result = 0;
        switch (name) {
            case "admin":
                for (int i = 0; i < seed.length(); i++) {
                    result += seed.charAt(i);
                }
                break;
            case "user1":
                result = 4242;
                break;
            case "user2":
                for (int i = 0; i < seed.length(); i++) {
                    result += (seed.charAt(i) * i) % 10;
                }
                break;
            default:
            result = -1;
                break;
        }
        return result;
    }
    public static void main(String[] args) {
        System.out.println("Please login");
        Boolean authenticated = false;
        while(!authenticated) {
            System.out.println("Enter your username:");
            String username = System.console().readLine();
            Integer generated = generatePassword(username);
            System.out.println(generated);
            if (generated == -1) {
                System.out.println("This user does not exist!");
                continue;
            }
            System.out.println("Enter your password:");
            String password = System.console().readLine();
            try {
                Integer.parseInt(password);
            } catch (NumberFormatException e) {
                System.out.println("Should be a number!");
                continue;
            }
            Integer passwordInt = Integer.parseInt(password);
            if (passwordInt.equals(generated)) {
                authenticated = true;
                System.out.println("Welcome " + username + "!");
            } else {
                System.out.println("Invalid username or password!");
            }
        }

    }
}