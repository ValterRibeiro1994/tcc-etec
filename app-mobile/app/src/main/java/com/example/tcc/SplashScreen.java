package com.example.tcc;

import android.content.Intent;
import android.os.Bundle;
import android.os.Handler;
import android.os.Looper;

import androidx.activity.EdgeToEdge;
import androidx.appcompat.app.AppCompatActivity;

import com.google.firebase.auth.FirebaseAuth;
import com.google.firebase.auth.FirebaseUser;

public class SplashScreen extends AppCompatActivity {

    private FirebaseAuth auth;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        ThemeManager.INSTANCE.aplicarTema(this);

        super.onCreate(savedInstanceState);

        EdgeToEdge.enable(this);
        setContentView(R.layout.activity_splash_screen);

        auth = FirebaseAuth.getInstance();

        new Handler(Looper.getMainLooper())
                .postDelayed(() -> {

                    FirebaseUser usuario = auth.getCurrentUser();

                    Intent intent;

                    if (usuario != null) {
                        // Usuário já está conectado
                        intent = new Intent(
                                SplashScreen.this,
                                MainActivity.class
                        );
                    } else {
                        // Usuário precisa fazer login
                        intent = new Intent(
                                SplashScreen.this,
                                LoginScreen.class
                        );
                    }

                    startActivity(intent);
                    finish();

                }, 2000);
    }
}
